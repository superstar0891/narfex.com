// styles
// external
import React from "react";
import { connect } from "react-redux";
// internal
// import SiteMainScreen from "src/landing/containers/MainScreen/MainScreen";
// import BuyBitcoinScreen from "src/landing/containers/BuyBitcoin/BuyBitcoin";
import SiteAboutScreen from "../landing/containers/Company/Company";
import SiteExchangeScreen from "../landing/containers/Exchange/Exchange";
import SiteContactScreen from "../landing/containers/Contacts/Contacts";
import SiteNotFoundScreen from "./containers/site/SiteNotFoundScreen/SiteNotFoundScreen";
import CabinetWrapper from "../wrappers/Cabinet/CabinetWrapper";
import LandingWrapper from "../wrappers/Landing/LandingWrapper";
import DocumentationWrapper from "../wrappers/Documentation/DocumentationWrapper";

import * as pages from "./constants/pages";
import * as CabinetWalletScreen from "./containers/cabinet/CabinetWalletScreen/CabinetWalletScreen";
import * as CabinetSettingsScreen from "./containers/cabinet/CabinetSettingsScreen/CabinetSettingsScreen";
import * as CabinetPartnersScreen from "./containers/cabinet/CabinetPartnersScreen/CabinerPartnersScreen";
import * as CabinetChangeEmail from "./containers/cabinet/CabinetChangeEmailScreen/CabinetChangeEmailScreen";
import * as CabinetTokenScreen from "./containers/cabinet/CabinetTokenScreen/CabinetTokenScreen.jsx";
import * as CabinetRegister from "./containers/cabinet/CabinetRegisterScreen/CabinetRegisterScreen";
import * as CabinetResetPassword from "./containers/site/SiteResetPasswordScreen/SiteResetPasswordScreen";
import * as MenuScreen from "./containers/cabinet/adaptive/MenuScreen/MenuScreen";
import * as NotificationsScreen from "./containers/cabinet/adaptive/NotificationsScreen/NotificationsScreen";
// import CabinetExchangeScreen from "./containers/cabinet/CabinetExchangeScreen/CabinetExchangeScreen";
import CabinetMerchantStatusScreen from "./containers/cabinet/CabinetMerchantStatusScreen/CabinetMerchantStatusScreen";
import SiteFeeScreen from "../landing/containers/Fee/SiteFeeScreen";
import SiteTokenScreen from "src/landing/containers/Token/Token";
import DocumentationPageScreen from "./containers/documentation/Page/Page";
import DocumentationMethodScreen from "./containers/documentation/Method/Method";
import DocumentationMethodListScreen from "./containers/documentation/MethodList/MethodList";
import * as actions from "../actions/index";
import router from "../router";

function Routes(props) {
  const routeState = props.route;
  const routerParams = routeState.params;
  const route = routeState.name;

  let Component;
  let WrapperComponent = CabinetWrapper;
  let needAuthorization = false;

  switch (route) {
    case pages.MAIN:
      // Component = SiteMainScreen;
      Component = SiteTokenScreen;
      WrapperComponent = LandingWrapper;
      break;
    // case pages.BUY_BITCOIN:
    //   Component = BuyBitcoinScreen;
    //   WrapperComponent = LandingWrapper;
    //   break;
    case pages.ABOUT:
      Component = SiteAboutScreen;
      WrapperComponent = LandingWrapper;
      break;
    // case pages.SITE_EXCHANGE:
    //   Component = SiteExchangeScreen;
    //   WrapperComponent = LandingWrapper;
    //   break;
    case pages.CONTACT:
      Component = SiteContactScreen;
      WrapperComponent = LandingWrapper;
      break;
    case pages.FEE:
      Component = SiteFeeScreen;
      WrapperComponent = LandingWrapper;
      break;
    case pages.TOKEN:
      Component = SiteTokenScreen;
      WrapperComponent = LandingWrapper;
      break;
    // case pages.WALLET_SWAP:
    case pages.WALLET:
    case pages.WALLET_CRYPTO:
      // case pages.WALLET_FIAT:
      needAuthorization = true;
      Component = CabinetWalletScreen.default;
      break;
    case pages.PARTNERS:
      needAuthorization = true;
      Component = CabinetPartnersScreen.default;
      break;
    case pages.SETTINGS:
      needAuthorization = true;
      Component = CabinetSettingsScreen.default;
      break;
    case pages.CHANGE_EMAIL:
      Component = CabinetChangeEmail.default;
      break;
    case pages.REGISTER:
      Component = CabinetRegister.default;
      break;
    case pages.RESET_PASSWORD:
      Component = CabinetResetPassword.default;
      break;
    case pages.MENU:
      needAuthorization = true;
      Component = MenuScreen.default;
      break;
    case pages.NOTIFICATIONS:
      needAuthorization = true;
      Component = NotificationsScreen.default;
      break;
    // case pages.EXCHANGE:
    //   Component = CabinetExchangeScreen;
    //   break;
    case pages.MERCHANT:
      WrapperComponent = props => <>{props.children}</>;
      Component = CabinetMerchantStatusScreen;
      break;
    case pages.DOCUMENTATION:
      WrapperComponent = DocumentationWrapper;
      Component = DocumentationPageScreen;
      break;
    case pages.DOCUMENTATION_PAGE:
      WrapperComponent = DocumentationWrapper;
      Component = DocumentationPageScreen;
      break;
    case pages.DOCUMENTATION_API:
      WrapperComponent = DocumentationWrapper;
      Component = DocumentationMethodListScreen;
      break;
    case pages.DOCUMENTATION_API_LIST:
      WrapperComponent = DocumentationWrapper;
      Component = DocumentationMethodListScreen;
      break;
    case pages.DOCUMENTATION_API_METHOD:
      WrapperComponent = DocumentationWrapper;
      Component = DocumentationMethodScreen;
      break;
    case pages.FNDR:
      needAuthorization = true;
      Component = CabinetTokenScreen.default;
      break;
    default:
      Component = SiteNotFoundScreen;
      break;
  }

  if (
    needAuthorization === true &&
    !props.profile.pending &&
    !props.profile.user
  ) {
    router.navigate(pages.MAIN);
    return null;
  }

  actions.setCabinet(WrapperComponent === CabinetWrapper); // HACK

  return (
    <WrapperComponent isHomepage={route === pages.MAIN}>
      <Component currentLang={props.currentLang} routerParams={routerParams} />
    </WrapperComponent>
  );
}

export default connect(state => ({
  currentLang: state.default.currentLang,
  profile: state.default.profile,
  route: state.router.route
}))(Routes);
