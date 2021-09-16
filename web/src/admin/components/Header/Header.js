import "./Header.less";
import React from "react";

import Logo from "../../../ui/components/Logo/Logo";
import * as auth from "../../../actions/authOld";
import { BaseLink } from "react-router5";
import router from "../../../router";
import * as PAGES from "../../constants/pages";
import ContentBox from "../../../ui/components/ContentBox/ContentBox";
import { IS_BITCOINOV_NET } from "../../../index/constants/cabinet";
import { Button } from "../../../ui";

export default props => {
  return (
    <ContentBox className="Header">
      <BaseLink router={router} routeName={PAGES.PANEL}>
        <Logo type={IS_BITCOINOV_NET ? "bitcoinovnet" : undefined} />
      </BaseLink>
      <div className="Header__title">Control Panel</div>
      <div className="Header__menu">
        <Button onClick={auth.logout} size="small" type="lite">
          Logout
        </Button>
      </div>
    </ContentBox>
  );
};
