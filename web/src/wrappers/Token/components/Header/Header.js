import "./Header.less";

import React, { useState } from "react";
import { connect } from "react-redux";

import SVG from "react-inlinesvg";
import { ActionSheet, Button, HamburgerButton } from "../../../../ui";
import { getCurrentLang } from "src/actions/index";
import { setLang } from "../../../../services/lang";
import { getLang } from "src/utils";
import router from "../../../../router";
import * as pages from "../../../../admin/constants/pages";
import { classNames as cn } from "src/utils";
import useDocumentScroll from "src/hooks/useDocumentScroll";
import Menu from "../Menu/Menu";

const Header = props => {
  const [menu, setMenu] = useState(false);
  const [scrollPosition, setScrollPosition] = useState(0);
  useDocumentScroll(setScrollPosition);
  const shadow = scrollPosition > 0;

  const handleMenu = () => setMenu(!menu);

  const links = [
    { title: getLang("token_whitePaper"), href: "#Main" },
    { title: getLang("token_Benefits"), href: "#Benefits" },
    { title: getLang("token_TokenData"), href: "#TokenData" },
    { title: getLang("token_Roadmap"), href: "#RoadMap" },
    { title: getLang("token_usability"), href: "#Usability" }
    // { title: getLang("token_SmartContract"), link: "Address" },
  ];

  return (
    <div className={cn("TokenWrapper__header", { shadow })}>
      <div className="TokenWrapper__content">
        <div className="TokenWrapper__header__logo">
          <div className="TokenWrapper__header__logo__normal">
            <SVG src={require("src/asset/token/header_logo.svg")} />
          </div>
          <div className="TokenWrapper__header__logo__mobile">
            <SVG src={require("src/asset/token/header_logo_mobile.svg")} />
          </div>
        </div>
        <div className="TokenWrapper__header__menu">
          {links.map((link, key) => (
            <a
              key={key}
              className={cn("TokenWrapper__header__menu__link", {
                active: window.location.hash === link.href
              })}
              href={link.href}
            >
              {link.title}
            </a>
          ))}

          <Button
            rounded
            size="small"
            onClick={() => {
              router.navigate(pages.MAIN);
            }}
            type="outline_white"
          >
            {getLang("token_MainSite")}
          </Button>
          <ActionSheet
            items={props.langList
              .filter(l => l.display)
              .map(i => ({
                title: i.title,
                onClick: () => setLang(i.value)
              }))}
          >
            <Button size="small" type="lite">
              <span>{getCurrentLang().value}</span>
              <SVG src={require("src/asset/16px/arrow-outline-down.svg")} />
            </Button>
          </ActionSheet>
        </div>

        <HamburgerButton
          active={menu}
          onClick={handleMenu}
          className={"TokenWrapper__header__hamburgerButton"}
        />
      </div>
      {menu && <Menu onClose={() => setMenu(false)} />}
    </div>
  );
};

export default connect(state => ({
  langList: state.default.langList,
  currentLang: state.default.currentLang
}))(Header);
