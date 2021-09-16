import "./CookieUsage.less";

import React from "react";
import { connect } from "react-redux";

import * as UI from "../../../../ui";
import { getLang } from "../../../../utils";
import * as storage from "../../../../services/storage";

function CookieUsage({ lang }) {
  const [isOpen, toggleOpen] = React.useState(true);

  const handleAgree = () => {
    storage.setItem("acceptedCookies", true);
    toggleOpen(false);
  };

  if (isOpen) {
    return (
      <div className="CookieUsage">
        <h3 className="CookieUsage__title">{getLang("site__cookieTitle")}</h3>
        <p className="CookieUsage__text">
          {getLang("site__cookieText1")}
          <span> {getLang("site__cookiePrivacyPolicy")}</span>
          {getLang("site__cookieText2")}
        </p>
        <UI.Button fontSize={15} onClick={handleAgree}>
          {getLang("site__cookieAgree")}
        </UI.Button>
      </div>
    );
  }

  return null;
}

const mapStateToProps = state => ({
  currentLang: state.default.currentLang
});

export default connect(mapStateToProps)(CookieUsage);
