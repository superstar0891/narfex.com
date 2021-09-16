import "./TabBar.less";
import React from "react";
import { connect } from "react-redux";
import { BaseLink } from "react-router5";
import router from "../../../../router";
import SVG from "react-inlinesvg";
import * as PAGES from "../../../constants/pages";
import Lang from "../../../../components/Lang/Lang";
import Badge from "../../../../ui/components/Badge/Badge";

const Tab = props => (
  <BaseLink
    router={router}
    routeName={props.route}
    className="TabBar__item"
    activeClassName="active"
  >
    {props.children}
  </BaseLink>
);

const Tabs = props => {
  return (
    <div className="TabBar">
      <Tab route={PAGES.WALLET}>
        <SVG src={require("../../../../asset/24px/wallet.svg")} />
        <span className="TabBar__item__text">
          <Lang name="cabinet_header_wallet" />
        </span>
      </Tab>
      {/*<Tab route={PAGES.EXCHANGE}>*/}
      {/*  <SVG src={require("../../../../asset/24px/candles.svg")} />*/}
      {/*  <span className="TabBar__item__text">*/}
      {/*    <Lang name="cabinet_header_exchange" />*/}
      {/*  </span>*/}
      {/*</Tab>*/}
      <Tab route={PAGES.PARTNERS}>
        <SVG src={require("../../../../asset/24px/users.svg")} />
        <span className="TabBar__item__text">
          <Lang name="cabinet_header_partners" />
        </span>
      </Tab>
      {/*<Tab route={PAGES.WALLET_SWAP}>*/}
      {/*  <div className="TabBar__item__primaryButton">*/}
      {/*    <SVG src={require("../../../../asset/24px/loop.svg")} />*/}
      {/*  </div>*/}
      {/*</Tab>*/}
      <Tab route={PAGES.FNDR}>
        <div className="TabBar__item__primaryButton">
          <SVG src={require("../../../../asset/24px/findiri.svg")} />
        </div>
      </Tab>
      <Tab route={PAGES.NOTIFICATIONS}>
        <Badge count={!!props.profile.has_notifications ? 1 : null}>
          <SVG src={require("../../../../asset/24px/bell.svg")} />
        </Badge>
        <span className="TabBar__item__text">
          <Lang name="global_notifications" />
        </span>
      </Tab>
      <Tab route={PAGES.MENU}>
        <SVG src={require("../../../../asset/24px/menu.svg")} />
        <span className="TabBar__item__text">
          <Lang name="cabinet_header_settings" />
        </span>
      </Tab>
    </div>
  );
};

export default connect(state => ({
  profile: state.default.profile,
  router: state.router
}))(Tabs);
