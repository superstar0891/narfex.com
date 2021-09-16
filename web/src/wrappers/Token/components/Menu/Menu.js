import "./Menu.less";
import React from "react";
import SVG from "react-inlinesvg";
import { Button } from "src/ui";
import { getLang } from "../../../../utils";
import router from "../../../../router";
import { getCurrentLang } from "src/actions/index";
import * as pages from "../../../../admin/constants/pages";
import * as actions from "../../../../actions";

export default props => {
  const currentLang = getCurrentLang();
  return (
    <div className="TokenWrapper__menu">
      <div className="TokenWrapper__menu__logo">
        <SVG src={require("src/asset/token/header_logo.svg")} />
      </div>
      <div className="TokenWrapper__menu__list">
        <a
          className="TokenWrapper__menu__link"
          onClick={props.onClose}
          href="#Main"
        >
          {getLang("token_whitePaper")}
        </a>
        <a
          className="TokenWrapper__menu__link"
          onClick={props.onClose}
          href="#Benefits"
        >
          {getLang("token_Benefits")}
        </a>
        <a
          className="TokenWrapper__menu__link"
          onClick={props.onClose}
          href="#TokenData"
        >
          {getLang("token_TokenData")}
        </a>
        <a
          className="TokenWrapper__menu__link"
          onClick={props.onClose}
          href="#RoadMap"
        >
          {getLang("token_Roadmap")}
        </a>
        <a
          className="TokenWrapper__menu__link"
          onClick={props.onClose}
          href="#Usability"
        >
          {getLang("token_usability")}
        </a>
        {/*<a className="TokenWrapper__menu__link" onClick={props.onClose} href="#Address">*/}
        {/*  {getLang("token_SmartContract")}*/}
        {/*</a>*/}
      </div>
      <div className={"TokenWrapper__menu__footer"}>
        <Button
          rounded
          size="middle"
          type="outline_white"
          onClick={() => {
            router.navigate(pages.MAIN);
          }}
        >
          {getLang("token_MainSite")}
        </Button>
        <Button
          size="middle"
          type="lite"
          onClick={() => {
            actions.openModal("language");
          }}
        >
          {currentLang.value.toUpperCase()} ({currentLang.title})
        </Button>
      </div>
    </div>
  );
};
