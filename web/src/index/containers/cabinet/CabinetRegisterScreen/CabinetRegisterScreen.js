import "./CabinetRegisterScreen.less";
//
import React from "react";
import { connect } from "react-redux";
import * as firebase from "firebase";

// import ReactPhoneInput from 'react-phone-input-2';
// import moment from 'moment';

import * as UI from "../../../../ui";
import { withRouter } from "react-router5";
import { GetParamsContext } from "../../../contexts";
import apiSchema from "../../../../services/apiSchema";
import * as api from "../../../../services/api";
import * as utils from "../../../../utils";
import * as pages from "../../../constants/pages";
// import SVG from 'react-inlinesvg';
import * as toastsActions from "../../../../actions/toasts";
import * as actions from "../../../../actions";
import { Helmet } from "react-helmet";
import COMPANY from "../../../constants/company";
import InputTooltip from "./comonents/Input/Input";
import Lang from "../../../../components/Lang/Lang";
import REGEXES from "src/index/constants/regexes";
import PasswordInfo from "./comonents/PasswordInfo/PasswordInfo";

class CabinetRegister extends React.PureComponent {
  state = {
    firstName: "",
    lastName: "",
    password: "",
    login: "",
    sendSmsTime: false,
    sendSmsStatus: null,
    timer: null,
    codeForm: false,
    pending: false,
    touched: false
  };

  componentDidMount() {
    this.props.setTitle(utils.getLang("cabinet_registerScreen_complete"));
    if (!this.context.params.hash) {
      this.props.router.navigate(pages.MAIN);
    } else {
      firebase.analytics().logEvent("open_registration_2step");
    }
  }

  __handleSubmit() {
    this.setState({ touched: true });

    const { params } = this.context;
    const { state } = this;

    // if (state.password && state.password.length < 6) {
    //   this.props.toastPush(utils.getLang("global_passwordMustBe"), "error");
    //   return false;
    // }

    // if (
    //   state.password &&
    //   state.passwordConfirm &&
    //   state.password !== state.passwordConfirm
    // ) {
    //   this.props.toastPush(
    //     utils.getLang("global_passwordsMustBeSame"),
    //     "error"
    //   );
    //   return false;
    // }

    if (
      utils.isLogin(state.login) &&
      utils.isName(state.firstName) &&
      utils.isName(state.lastName) &&
      utils.isPassword(state.password)
      // state.phoneWithoutCode &&
      // state.smsCode
    ) {
      this.setState({ pending: true });
      api
        .call(apiSchema.Profile.FillAccountPut, {
          first_name: state.firstName,
          last_name: state.lastName,
          login: state.login,
          password: state.password,
          // phone_code: state.dialCode,
          // phone_number: state.phoneWithoutCode,
          // sms_code: state.smsCode,
          hash: params.hash
        })
        .then(({ access_token }) => {
          firebase.analytics().logEvent("registration_2step");
          this.props.toastPush(
            utils.getLang("cabinet_registerScreen_success"),
            "success"
          );
          window.location.href = "/" + pages.WALLET;
        })
        .catch(err => {
          this.props.toastPush(err.message, "error");
        })
        .finally(() => {
          this.setState({ pending: false });
        });
    }
  }

  __handleChange(name, value) {
    this.setState({ [name]: value });
  }

  static contextType = GetParamsContext;

  render() {
    if (!this.context.params.hash) {
      return false;
    }
    const { state } = this;

    const validFirstName =
      state.firstName && REGEXES.name.test(state.firstName);
    const validLastName = state.lastName && REGEXES.name.test(state.lastName);
    const validLogin = state.login && REGEXES.login.test(state.login);

    const validPassword = utils.isPassword(state.password);

    const { adaptive } = this.props;

    return (
      <div className="CabinetRegister">
        <Helmet>
          <title>
            {[
              COMPANY.name,
              utils.getLang("cabinet_registerScreen_complete")
            ].join(" - ")}
          </title>
        </Helmet>
        <UI.ContentBox className="CabinetRegister__content">
          <h3 className="CabinetRegister__content__title">
            {utils.getLang("cabinet_registerScreen_complete")}
          </h3>
          <InputTooltip
            error={state.touched && !validFirstName}
            title={<Lang name="registration_firstNameDescription" />}
            value={state.firstName}
            placeholder={utils.getLang(
              "cabinet_registerScreen_firstName",
              true
            )}
            onTextChange={text => this.__handleChange("firstName", text)}
          />
          <InputTooltip
            error={state.touched && !validLastName}
            title={<Lang name="registration_lastNameDescription" />}
            value={state.lastName}
            placeholder={utils.getLang("cabinet_registerScreen_lastName", true)}
            onTextChange={text => this.__handleChange("lastName", text)}
          />
          <InputTooltip
            error={state.touched && !(state.login && validLogin)}
            title={<Lang name="registration_loginDescription" />}
            value={state.login}
            placeholder={utils.getLang("site__contactLogin", true)}
            onTextChange={text => this.__handleChange("login", text)}
          />

          <h3 className="CabinetRegister__content__title">
            {utils.getLang("cabinet_registerScreen_createPassword")}
          </h3>
          <InputTooltip
            error={state.touched && !validPassword}
            value={state.password}
            title={<PasswordInfo password={state.password} />}
            type="password"
            placeholder={utils.getLang("cabinet_registerScreen_password", true)}
            onTextChange={text => this.__handleChange("password", text)}
          />

          <InputTooltip
            error={
              state.touched &&
              (!state.passwordConfirm ||
                state.passwordConfirm !== state.password)
            }
            title={
              state.touched &&
              state.passwordConfirm !== state.password &&
              (!adaptive || validPassword) && (
                <Lang name="global_passwordsMustBeSame" />
              )
            }
            value={state.passwordConfirm}
            type="password"
            placeholder={utils.getLang(
              "cabinet_registerScreen_reEnterPassword",
              true
            )}
            onTextChange={text => this.__handleChange("passwordConfirm", text)}
          />

          <div className="CabinetRegister__content__submit_wrapper">
            <UI.Button
              state={this.state.pending && "loading"}
              onClick={this.__handleSubmit.bind(this)}
            >
              {utils.getLang("site__commerceRegistration")}
            </UI.Button>
          </div>
        </UI.ContentBox>
      </div>
    );
  }
}

export default connect(
  state => ({
    translator: state.settings.translator,
    currentLang: state.default.currentLang,
    adaptive: state.default.adaptive
  }),
  {
    toastPush: toastsActions.toastPush,
    setTitle: actions.setTitle
  }
)(withRouter(CabinetRegister));
