// styles
// external
import React from "react";
import { connect } from "react-redux";
// internal
import MainScreen from "./containers/MainScreen/MainScreen";
import * as pages from "./constants/pages";
import SiteNotFoundScreen from "../index/containers/site/SiteNotFoundScreen/SiteNotFoundScreen";
import router from "../router";
import LandingWrapperr from "../wrappers/Landing/LandingWrapper";

function Routes(props) {
  const routerParams = props.route.params;
  const route = props.route.name;

  let Component = null;
  let WrapperComponent = props => <>{props.children}</>;

  if (route !== pages.MAIN && !props.pending && !props.user) {
    router.navigate(pages.MAIN);
  }

  switch (route) {
    case pages.MAIN:
      Component = MainScreen;
      WrapperComponent = LandingWrapperr;
      break;
    default:
      Component = SiteNotFoundScreen;
      break;
  }

  return (
    <WrapperComponent>
      <Component routerParams={routerParams} />
    </WrapperComponent>
  );
}

export default connect(state => ({
  user: state.default.profile.user,
  route: state.router.route
}))(Routes);
