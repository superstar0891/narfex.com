import "../index.less";
// import '../index/vars.less';

import React from "react";
import { connect } from "react-redux";

import Toasts from "../index/components/cabinet/Toasts/Toasts";
import Routes from "./Routes";
import Modals from "./Modals";
import DynamcModals from "./DynamcModals";
import { choseLang, getLang } from "../services/lang";
import * as actions from "../actions";
import LoadingStatus from "../index/components/cabinet/LoadingStatus/LoadingStatus";

class App extends React.Component {
  state = {
    isLoading: true
  };

  componentDidMount() {
    this._loadAssets();
  }

  render() {
    if (this.state.isLoading || this.props.profile.pending) {
      return <LoadingStatus status="loading" />;
    }

    return (
      <div>
        <Routes />
        <DynamcModals modals={this.props.modals} />
        <Modals />
        <Toasts />
      </div>
    );
  }

  _loadAssets = () => {
    const lang = getLang();
    choseLang(lang);
    Promise.all([actions.loadLang(lang), actions.loadCurrencies()]).then(() => {
      this.setState({ isLoading: false });
    });
  };
}

export default connect(state => ({
  // ...state,
  profile: state.default.profile,
  modals: state.admin.modals
}))(App);
