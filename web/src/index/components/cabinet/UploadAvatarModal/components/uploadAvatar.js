import React from "react";
import * as UI from "../../../../../ui";
import * as auth from "../../../../../services/auth";
import * as api from "../../../../../services/api";
import * as emitter from "../../../../../services/emitter";
import * as utils from "../../../../../utils";

export default class ImageUpload extends React.Component {
  constructor(props) {
    super(props);
    this.uploadPhotoForm = React.createRef();
    this.fileInput = React.createRef();
  }

  state = {
    file: "",
    imagePreviewUrl: "",
    percentsOfComplete: 0
  };

  _handleSubmit = e => {
    e.preventDefault();
    if (!this.state.file) return;

    let uploader = new XMLHttpRequest();
    let url = `${api.API_ENTRY}/api/v${api.API_VERSION}/profile/upload_photo`;

    uploader.open("POST", url, true);
    uploader.setRequestHeader("X-Token", auth.getToken());
    uploader.setRequestHeader(
      "Accept-Language",
      window.localStorage.lang || "en"
    );

    uploader.upload.onprogress = event => {
      this.setState({
        percentsOfComplete: Math.ceil((event.loaded / event.total) * 100)
      });
    };

    uploader.onload = e => {
      let response = JSON.parse(e.currentTarget.response);
      if (response.hasOwnProperty("response")) {
        emitter.emit("userInstall");
        this.props.onClose();
      } else {
        console.error("status" + response.status, "error upload");
      }
    };

    uploader.onerror = () => {
      let response = JSON.parse(this.response);
      if (response.status === 200) {
        //
      } else {
        console.error("status" + response.status, "error upload");
      }
    };

    uploader.send(new FormData(this.uploadPhotoForm.current));
  };

  _handleImageChange(e) {
    e.preventDefault();

    let reader = new FileReader();
    let file = e.target.files[0];

    reader.onloadend = () => {
      this.setState({
        file: file,
        imagePreviewUrl: reader.result
      });
    };

    reader.readAsDataURL(file);
  }

  render() {
    let imagePreview = this.state.imagePreviewUrl ? (
      <div
        className="img"
        style={{ backgroundImage: `url(${this.state.imagePreviewUrl})` }}
      >
        {" "}
      </div>
    ) : (
      ""
    );

    return (
      <div className="UploadAvatar__padding_preview">
        <form
          name="uploadPhotoForm"
          id="uploadPhotoForm"
          ref={this.uploadPhotoForm}
          onSubmit={e => this._handleSubmit(e)}
        >
          <input
            style={{ display: "none" }}
            ref={this.fileInput}
            className="fileInput"
            type="file"
            name="file"
            onChange={e => this._handleImageChange(e)}
          />
          {this.state.percentsOfComplete < 1 ? (
            <div className="UploadAvatar__imgPreview">{imagePreview}</div>
          ) : (
            <div className="UploadAvatar__percents">
              {this.state.percentsOfComplete}
            </div>
          )}
          <div className="UploadAvatar__padding"></div>
          <div className="UploadAvatar__button">
            {!this.state.file ? (
              <UI.Button
                style={
                  this.state.percentsOfComplete > 0 ? { display: "none" } : {}
                }
                type="secondary"
                size="small"
                onClick={() => this.fileInput.current.click()}
              >
                {utils.getLang("cabinet_uploadAvatarModal_select")}
              </UI.Button>
            ) : (
              this.state.percentsOfComplete > 0 || (
                <UI.Button
                  type="secondary"
                  size="small"
                  onClick={() => {
                    this.setState({
                      file: "",
                      imagePreviewUrl: "",
                      percentsOfComplete: 0
                    });
                  }}
                >
                  {utils.getLang("cabinet_uploadAvatarModal_cancel")}
                </UI.Button>
              )
            )}
          </div>
          <div className="UploadAvatar__padding"></div>
          <div className="UploadAvatar__button">
            {this.state.file ? (
              <UI.Button
                className=""
                size="small"
                disabled={this.state.percentsOfComplete > 0}
                state={
                  this.state.percentsOfComplete > 0 &&
                  this.state.percentsOfComplete < 100
                    ? "loading"
                    : null
                }
                onClick={e => this._handleSubmit(e)}
              >
                {utils.getLang("cabinet_uploadAvatarModal_uploadImage")}
              </UI.Button>
            ) : (
              ""
            )}
          </div>
        </form>
      </div>
    );
  }
}
