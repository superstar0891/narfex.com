// styles
// external
import React from "react";
import { connect } from "react-redux";
// internal
import action from "../actions/admin";
import MainScreen from "./containers/MainScreen/MainScreen";
import PanelScreen from "./containers/PanelScreen/PanelScreen";
import * as pages from "./constants/pages";
import SiteNotFoundScreen from "../index/containers/site/SiteNotFoundScreen/SiteNotFoundScreen";
import AdminWrapper from "../wrappers/Admin/AdminWrapper";
import router from "../router";

router.addListener((state, prevState) => {
  const page = router.getState().params.page;
  const ignorePages = ["Translations"];
  if (
    state.name === pages.PANEL_PAGE &&
    !state.params.modal &&
    !ignorePages.includes(page) &&
    (!prevState || state.name !== prevState.name)
  ) {
    action({
      type: "show_page",
      params: {
        page: router.getState().params.page
      }
    });
  }
});

function Routes(props) {
  const routerParams = props.route.params;
  const route = props.route.name;

  let actions = {};
  let Component = null;
  let WrapperComponent = props => <>{props.children}</>;

  if (route !== pages.MAIN && !props.pending && !props.user) {
    router.navigate(pages.MAIN);
  }

  if (route === pages.MAIN && props.user) {
    router.navigate(pages.PANEL);
  }

  switch (route) {
    case pages.MAIN:
      Component = MainScreen;
      break;
    case pages.PANEL:
    case pages.PANEL_PAGE:
      Component = PanelScreen;
      WrapperComponent = AdminWrapper;
      break;
    default:
      Component = SiteNotFoundScreen;
      break;
  }

  // const defaultProps = {
  //   state: props.state.default,
  //   router: props.router,
  // };

  return (
    <WrapperComponent>
      <Component {...actions} routerParams={routerParams} />
    </WrapperComponent>
  );
}

export default connect(state => ({
  user: state.default.profile.user,
  route: state.router.route,
  pending: state.admin.pending || state.default.profile.pending
}))(Routes);
