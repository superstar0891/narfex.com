import "./RegisterBanner.less";

import React, { useState } from "react";
import { connect } from "react-redux";

import { classNames } from "../../../../utils";
import * as actions from "../../../../actions";
import { registrationSetValue } from "src/actions/index";
import { getLang } from "../../../../utils";

function RegisterBanner({ isCurly, registrationSetValue }) {
  const [email, changeEmail] = useState("");
  const [isInputActive, toggleInputActive] = useState(false);

  const className = classNames({
    RegisterBanner: true,
    curly: isCurly,
    active: isInputActive
  });

  return (
    <div className={className}>
      <div className="RegisterBanner__content">
        <div className="RegisterBanner__title">
          {getLang("site__registerBannerTitle")}
        </div>
        <div className="RegisterBanner__caption">
          {getLang("site__registerBannerCaption")}
        </div>
        <div className="RegisterBanner__form">
          <input
            type="email"
            className="RegisterBanner__form__input"
            placeholder={getLang("site__authModalPlaceholderEmail", true)}
            value={email}
            onChange={e => changeEmail(e.target.value)}
            onFocus={() => toggleInputActive(true)}
            onBlur={() => toggleInputActive(false)}
          />
          <div
            onClick={() => {
              registrationSetValue("email", email);
              actions.openModal("registration");
            }}
            className="RegisterBanner__form__button"
          >
            {getLang("site__registerBannerBtn")}
          </div>
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = state => ({
  currentLang: state.default.currentLang
});

export default React.memo(
  connect(mapStateToProps, { registrationSetValue })(RegisterBanner)
);
