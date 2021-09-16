import "./SiteResetPasswordScreen.less";

import React from "react";
import { connect } from "react-redux";

import * as UI from "../../../../ui";
import { withRouter } from "react-router5";
import { GetParamsContext } from "../../../contexts";
import apiSchema from "../../../../services/apiSchema";
import * as api from "../../../../services/api";
import * as utils from "../../../../utils";
import * as actions from "../../../../actions";
import * as toastsActions from "../../../../actions/toasts";
import COMPANY from "../../../constants/company";
import { Helmet } from "react-helmet";
import PasswordInfo from "../../cabinet/CabinetRegisterScreen/comonents/PasswordInfo/PasswordInfo";
import InputTooltip from "../../cabinet/CabinetRegisterScreen/comonents/Input/Input";
import Lang from "../../../../components/Lang/Lang";

class CabinetRegister extends React.PureComponent {
  state = {
    success: false,
    pending: false
  };

  componentDidMount() {
    this.props.setTitle(utils.getLang("cabinet_resetPassword_title"));
  }

  static contextType = GetParamsContext;

  __handleChange(name, value) {
    this.setState({ [name]: value });
  }

  __handleSubmit() {
    const { params } = this.context;
    const { state } = this;

    this.setState({ touched: true });

    if (state.password && state.password.length < 6) {
      this.props.toastPush(utils.getLang("global_passwordMustBe"), "error");
      return false;
    }

    if (
      state.password &&
      state.passwordConfirm &&
      state.password !== state.passwordConfirm
    ) {
      this.props.toastPush(
        utils.getLang("global_passwordsMustBeSame"),
        "error"
      );
      return false;
    }

    if (state.password) {
      this.setState({ pending: true });
      api
        .call(apiSchema.Profile.ResetPasswordPut, {
          password: state.password,
          hash: params.hash
        })
        .then(({ access_token }) => {
          this.setState({ success: true });
        })
        .catch(err => {
          this.props.toastPush(err.message, "error");
        })
        .finally(() => {
          this.setState({ pending: false });
        });
    }
  }

  render() {
    if (!this.context.params.hash) {
      return false;
    }
    const { state } = this;
    const confirmPasswordError =
      !state.passwordConfirm || state.passwordConfirm !== state.password;
    return (
      <div className="CabinetResetPassword">
        <Helmet>
          <title>
            {[
              COMPANY.name,
              utils.getLang("cabinet_resetPassword_title", true)
            ].join(" - ")}
          </title>
        </Helmet>
        <div className="CabinetResetPassword__content Content_box">
          {this.state.success ? (
            <div>
              <div
                className="CabinetResetPassword__content__icon"
                style={{
                  backgroundImage: `url(${require("../../../../asset/120/success.svg")})`
                }}
              />
              <p>{utils.getLang("cabinet_resetPasswordSuccess")}</p>
              <UI.Button
                fontSize={15}
                onClick={() => {
                  window.location.href = "/"; // TODO: после правки модалок на лендинге, заменить этот код на openModal()
                }}
              >
                {utils.getLang("site__headerLogIn")}
              </UI.Button>
            </div>
          ) : (
            <div>
              <h3 className="CabinetResetPassword__content__title">
                {utils.getLang("cabinet_resetPassword_title")}
              </h3>
              <InputTooltip
                type="password"
                error={state.touched && !state.password}
                title={<PasswordInfo password={state.password} />}
                value={state.password}
                placeholder={utils.getLang(
                  "cabinet_registerScreen_password",
                  true
                )}
                onTextChange={text => this.__handleChange("password", text)}
              />
              <InputTooltip
                type="password"
                error={state.touched && confirmPasswordError}
                title={
                  state.touched &&
                  confirmPasswordError && (
                    <Lang name="global_passwordsMustBeSame" />
                  )
                }
                value={state.passwordConfirm}
                placeholder={utils.getLang(
                  "cabinet_registerScreen_reEnterPassword",
                  true
                )}
                onTextChange={text =>
                  this.__handleChange("passwordConfirm", text)
                }
              />
              <div className="CabinetResetPassword__content__submit_wrapper">
                <UI.Button
                  state={this.state.pending && "loading"}
                  fontSize={15}
                  onClick={this.__handleSubmit.bind(this)}
                >
                  {utils.getLang("cabinet_resetPassword_title")}
                </UI.Button>
              </div>
            </div>
          )}
        </div>
      </div>
    );
  }
}

export default connect(null, {
  setTitle: actions.setTitle,
  toastPush: toastsActions.toastPush
})(withRouter(CabinetRegister));
