import "./MenuScreen.less";
//
import React from "react";
import SVG from "react-inlinesvg";
import { BaseLink } from "react-router5";
import { connect } from "react-redux";
//
import router from "../../../../../router";
import CabinetBaseScreen from "../../CabinetBaseScreen/CabinetBaseScreen";
import * as PAGES from "../../../../constants/pages";
import * as utils from "../../../../../utils";
import * as auth from "../../../../../actions/authOld";
import * as actions from "../../../../../actions";
import ContentBox from "src/ui/components/ContentBox/ContentBox";
import Lang from "../../../../../components/Lang/Lang";
import { userRole } from "../../../../../actions/cabinet/profile";

class MenuScreen extends CabinetBaseScreen {
  componentDidMount() {
    this.props.setTitle(utils.getLang("global_menu"));
  }

  render() {
    if (!this.props.adaptive) {
      return null;
    }

    const lang = actions.getCurrentLang();

    return (
      <div className="Menu">
        <ContentBox className="Menu__section">
          <div className="Menu__section__title">
            {utils.getLang("cabinet_header_settings")}
          </div>
          <BaseLink
            router={router}
            routeName={PAGES.SETTINGS}
            className="Menu__section__item"
            activeClassName="active"
          >
            <SVG src={require("../../../../../asset/24px/id-badge.svg")} />
            <span>{utils.getLang("cabinet_settingsMenuPersonal")}</span>
          </BaseLink>
          <BaseLink
            router={router}
            routeName={PAGES.SETTINGS}
            routeParams={{ section: "security" }}
            className="Menu__section__item"
            activeClassName="active"
          >
            <SVG src={require("../../../../../asset/24px/shield.svg")} />
            <span>{utils.getLang("global_security")}</span>
          </BaseLink>
          <BaseLink
            router={router}
            routeName={PAGES.SETTINGS}
            routeParams={{ section: "apikey" }}
            className="Menu__section__item"
            activeClassName="active"
          >
            <SVG src={require("../../../../../asset/24px/key.svg")} />
            <span>{utils.getLang("cabinet_apiKey")}</span>
          </BaseLink>
        </ContentBox>

        {(this.props.profile.has_deposits || userRole("agent")) && (
          <ContentBox className="Menu__section">
            {(this.props.profile.has_deposits || userRole("agent")) && (
              <BaseLink
                router={router}
                routeName={PAGES.PARTNERS}
                className="Menu__section__item"
                activeClassName="active"
              >
                <SVG src={require("../../../../../asset/24px/users.svg")} />
                <span>
                  <Lang name="cabinet_header_partners" />
                </span>
              </BaseLink>
            )}
          </ContentBox>
        )}

        <ContentBox className="Menu__section Menu__section__noSpacing">
          <div
            onClick={this.__handleChangeLanguage}
            className="Menu__section__item"
          >
            <SVG
              className={"Menu__section__item__flag"}
              src={require(`../../../../../asset/site/lang-flags/${lang.value}.svg`)}
            />
            <span>
              {lang.title} {lang.value.toUpperCase()}
            </span>
          </div>
          {/*<div*/}
          {/*  onClick={this.__handleToggleTheme}*/}
          {/*  className="Menu__section__item"*/}
          {/*>*/}
          {/*  <SVG src={require(`src/asset/24px/sun.svg`)} />*/}
          {/*  <span>{utils.getLang("global_darkMode")}</span>*/}
          {/*  <Switch on={this.props.theme === 'dark'} />*/}
          {/*</div>*/}
        </ContentBox>
        <ContentBox className="Menu__section Menu__section__noSpacing">
          <BaseLink
            router={router}
            onClick={auth.logout}
            routeName={PAGES.MAIN}
            className="Menu__section__item"
            activeClassName="active"
          >
            <SVG src={require("../../../../../asset/24px/exit.svg")} />
            <span>{utils.getLang("cabinet_header_exit")}</span>
          </BaseLink>
        </ContentBox>
      </div>
    );
  }

  __handleToggleTheme = () => {
    actions.toggleTheme();
  };

  __handleChangeLanguage = () => {
    actions.openModal("language");
  };
}

export default connect(
  state => ({
    adaptive: state.default.adaptive,
    langList: state.default.langList,
    theme: state.default.theme,
    profile: state.default.profile
  }),
  {
    setTitle: actions.setTitle
  }
)(MenuScreen);
