import "./VerificationBlock.less";

import React from "react";
import { connect } from "react-redux";

import * as utils from "../../../../../../utils";
import * as UI from "../../../../../../ui";
import * as actions from "../../../../../../actions";
import SVG from "react-inlinesvg";
import { ButtonWrapper } from "../../../../../../ui/components/Button/Button";

const VerificationBlock = props => {
  const statuses = {
    not_verified: {
      status: utils.getLang("cabinet_verificationStatus_not_verified"),
      icon: require("src/asset/120/verify.svg"),
      action: true
    },
    rejected: {
      status: utils.getLang("cabinet_verificationStatus_rejected"),
      icon: require("src/asset/120/verification_blocked.svg"),
      action: false
    },
    pending: {
      status: utils.getLang("cabinet_verificationStatus_pending"),
      icon: require("src/asset/120/verification_pending.svg"),
      action: false
    },
    temporary_rejected: {
      status: utils.getLang("cabinet_verificationStatus_temporary_rejected"),
      icon: require("src/asset/120/verification_required_info.svg"),
      action: true
    },
    verified: {
      status: utils.getLang("cabinet_verificationStatus_temporary_success"),
      icon: require("src/asset/120/verification_success.svg"),
      action: false
    }
  };

  const status = statuses[props.verification];

  return (
    <UI.ContentBox className="CabinetSettingsScreen__main VerificationBlock">
      <div className="CabinetSettingsScreen__header">
        {utils.getLang("global_verification")}
      </div>
      <div className="CabinetSettingsScreen__content">
        <div className="CabinetSettingsScreen__content__status">
          <SVG src={status.icon} />
          <div className="CabinetSettingsScreen__content__status__text">
            {status.status}
          </div>
        </div>
        <div className="CabinetSettingsScreen__content__text">
          <p>{utils.getLang("cabinet_profile_verificationText")}</p>
          <ul>
            <li>{utils.getLang("cabinet_profile_verificationItem1")}</li>
            <li>{utils.getLang("cabinet_profile_verificationItem2")}</li>
            <li>{utils.getLang("cabinet_profile_verificationItem3")}</li>
            <li>{utils.getLang("cabinet_profile_verificationItem4")}</li>
          </ul>

          {
            <ButtonWrapper align="right">
              <UI.Button onClick={() => actions.openModal("verification")}>
                {utils.getLang("cabinet_settings_passVerification")}
              </UI.Button>
            </ButtonWrapper>
          }
        </div>
      </div>
    </UI.ContentBox>
  );
};

export default connect(state => ({
  verification: state.default.profile.verification,
  currentLang: state.default.currentLang,
  translator: state.settings.translator
}))(VerificationBlock);
