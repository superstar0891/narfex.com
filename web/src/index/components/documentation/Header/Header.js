import "./Header.less";
import React from "react";
import { connect } from "react-redux";

import Logo from "src/ui/components/Logo/Logo";
import { BaseLink } from "react-router5";
import router from "src/router";
import * as PAGES from "src/index/constants/pages";
import ContentBox from "src/ui/components/ContentBox/ContentBox";
import * as UI from "../../../../ui";
import * as actions from "../../../../actions";
import * as utils from "../../../../utils";
import SVG from "react-inlinesvg";
import * as pages from "../../../constants/pages";
import COMPANY from "../../../constants/company";
import * as auth from "../../../../actions/authOld";

const Header = props => {
  const lang = actions.getCurrentLang();
  return (
    <ContentBox className="DocumentationHeader">
      <BaseLink router={router} routeName={PAGES.MAIN}>
        <Logo />
      </BaseLink>
      <div className="DocumentationHeader__title">
        {utils.getLang("cabinet_docsTitle")}
      </div>
      {/*<div className="Header__menu"></div>*/}
      <div className="DocumentationHeader__controls">
        <UI.ActionSheet
          position="left"
          items={[
            !props.isLogged && {
              title: utils.getLang("site__authModalLogInBtn"),
              onClick: () => actions.openModal("login")
            },
            !props.isLogged && {
              title: utils.getLang("site__authModalSignUpBtn"),
              onClick: () => actions.openModal("registration")
            },
            props.isLogged && {
              title: utils.getLang("cabinet_header_settings"),
              onClick: () => router.navigate(pages.SETTINGS)
            },
            {
              title: "FAQ",
              onClick: () => window.open(COMPANY.faqUrl)
            },
            {
              title: lang.title,
              onClick: () => actions.openModal("language"),
              subContent: (
                <SVG
                  src={require(`../../../../asset/site/lang-flags/${lang.value}.svg`)}
                />
              )
            },
            props.isLogged && {
              title: utils.getLang("cabinet_header_exit"),
              onClick: auth.logout
            }
          ].filter(Boolean)}
        >
          <SVG
            className="DocumentationHeader__dropDownMenuIcon"
            src={require("../../../../asset/cabinet/settings.svg")}
          />
        </UI.ActionSheet>
      </div>
    </ContentBox>
  );
};

export default connect(state => ({
  lang: state.default.currentLang,
  isLogged: state.default.profile.user
}))(Header);
