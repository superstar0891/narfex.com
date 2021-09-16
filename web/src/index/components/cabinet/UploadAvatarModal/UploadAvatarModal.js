import "./UploadAvatarModal.less";

import React from "react";
import * as UI from "../../../../ui";
import "./UploadAvatarModal.less";

import UploadAvatar from "./components/uploadAvatar";
import * as utils from "../../../../utils";

export default class UploadAvatarModal extends React.Component {
  render() {
    return (
      <UI.Modal isOpen={true} onClose={this.props.onClose} width={384}>
        <UI.ModalHeader>
          {utils.getLang("cabinet_uploadAvatarModal_name")}
        </UI.ModalHeader>
        <div>
          <UploadAvatar />
        </div>
      </UI.Modal>
    );
  }
}
