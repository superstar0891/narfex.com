import "./CabinetChangeEmailScreen.less";
//
import React from "react";
import { connect } from "react-redux";
import { withRouter } from "react-router5";
//
import * as UI from "../../../../ui";
import { setTitle } from "../../../../actions/index";
import LoadingStatus from "../../../components/cabinet/LoadingStatus/LoadingStatus";
import apiSchema from "../../../../services/apiSchema";
import * as api from "../../../../services/api";
import * as pages from "../../../constants/pages";
import * as utils from "../../../../utils";

class CabinetChangeEmail extends React.PureComponent {
  state = {
    success: null,
    pending: true,
    message: utils.getLang("cabinet_changeEmailScreenSuccess")
  };

  componentDidMount() {
    this.props.setTitle(utils.getLang("cabinet_changeEmailModal_name"));
    const { params } = this.props.router.getState();

    api
      .call(apiSchema.Profile.ConfirmEmailPost, params)
      .then(() => {
        this.setState({ success: true, pending: false });
      })
      .catch(err => {
        this.setState({ pending: false, message: err.message });
      });
  }

  render() {
    if (this.state.pending) {
      return <LoadingStatus status="loading" />;
    }
    return (
      <div className="CabinetChangeEmail">
        <UI.ContentBox className="CabinetChangeEmail__content">
          {this.state.success && (
            <div
              className="CabinetChangeEmail__content__icon"
              style={{
                backgroundImage: `url(${require("../../../../asset/120/success.svg")})`
              }}
            />
          )}
          <p>{this.state.message}</p>
          <UI.Button onClick={() => this.props.router.navigate(pages.WALLET)}>
            {utils.getLang("global_understand")}
          </UI.Button>
        </UI.ContentBox>
      </div>
    );
  }
}

export default connect(null, {
  setTitle
})(withRouter(CabinetChangeEmail));
