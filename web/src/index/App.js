// styles
// external
import React from "react";
import { connect } from "react-redux";
import { loadReCaptcha } from "react-recaptcha-google";
// internal
import Routes from "./Routes";
import Modals from "./Modals";
import Toasts from "./components/cabinet/Toasts/Toasts";
import CookieUsage from "./components/site/CookieUsage/CookieUsage";
import * as UI from "../ui";
import * as actions from "../actions";
import * as internalNotifications from "../actions/cabinet/internalNotifications";
import * as storage from "../services/storage";
import { getLang, choseLang } from "../services/lang";
import * as utils from "../utils";
import router from "src/router";
import { Helmet } from "react-helmet";
import { classNames as cn } from "../utils";
import { setAdminToken } from "../services/auth";
import * as PAGES from "./constants/pages";
import LoadingStatus from "./components/cabinet/LoadingStatus/LoadingStatus";

class App extends React.Component {
  state = {
    isLoading: true,
    error: null
  };

  componentDidMount() {
    const { admin_token: token } = this.props.route.params;
    if (token) {
      setAdminToken(token);
      router.navigate(PAGES.CABINET);
      window.location.reload();
    }

    document.body.classList.add(["theme", this.props.theme].join("-"));
    loadReCaptcha();
    this._loadAssets();
  }

  componentDidCatch(error, info) {
    this.setState({
      error: {
        name: error.name,
        message: error.message
        // message: error.message + " " + error.stack.toString(),
      }
    });
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (prevProps.theme !== this.props.theme) {
      document.body.classList.remove(["theme", prevProps.theme].join("-"));
      document.body.classList.add(["theme", this.props.theme].join("-"));
    }
    if (this.props.modal.name || this.props.route.params.modal) {
      document.body.classList.add("modal-open");
      document.body.style.marginRight = utils.getScrollbarWidth() + "px";
    } else {
      document.body.classList.remove("modal-open");
      document.body.style.marginRight = 0;
    }
  }

  render() {
    const acceptedCookies = storage.getItem("acceptedCookies");
    const { error } = this.state;

    if (this.state.isLoading) {
      return <LoadingStatus status="loading" />;
    }

    if (error) {
      return (
        <div className="Error_wrapper">
          <UI.Message type="error">
            <h2>{error.name}</h2>
            <p>{error.message}</p>
          </UI.Message>
        </div>
      );
    }

    return (
      <div className={cn({ adaptive: this.props.adaptive })}>
        <Helmet>
          <title>{utils.getLang("global_meta_title", true)}</title>
        </Helmet>
        <Modals
          modal={this.props.route.params.modal}
          params={this.props.route.params}
        />
        <Modals
          state={true}
          modal={this.props.modal.name}
          params={this.props.modal.params}
        />
        <Routes />
        <Toasts />
        {!acceptedCookies ? <CookieUsage /> : null}
      </div>
    );
  }

  _loadAssets = () => {
    const lang = getLang();
    choseLang(lang);
    actions
      .loadLang(lang)
      .then(() => {
        // actions.loadCurrencies();
        this.setState({ isLoading: false });
      })
      .catch(() => setTimeout(this._loadAssets, 3000));
  };
}

export default connect(
  state => ({
    adaptive: state.default.adaptive,
    modal: state.modal,
    route: state.router.route,
    theme: state.default.cabinet ? state.default.theme : "light"
  }),
  {
    loadInternalNotifications: internalNotifications.load
  }
)(App);
