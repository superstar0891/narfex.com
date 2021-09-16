import "./PanelScreen.less";

import React from "react";
import { connect } from "react-redux";

import LangsScreen from "../LangsScreen/LangsScreen";

import Item from "../../components/Item/Item";
import * as pages from "../../constants/pages";
import * as adminActions from "../../../actions/admin";

class PanelScreen extends React.Component {
  componentDidMount() {
    adminActions.init();
  }

  render() {
    if (this.props.route.name === pages.PANEL) {
      return (
        <div className="PanelScreen">
          <div className="PanelScreen__placeholder">
            Select a page in the sidebar
          </div>
        </div>
      );
    }
    if (this.props.route.params.page === "Translations") {
      return <LangsScreen />;
    }
    return (
      <div className="PanelScreen">
        <Item item={this.props.layout} />
      </div>
    );
  }
}

export default connect(state => ({
  layout: state.admin.layout,
  user: state.default.profile.user,
  route: state.router.route
}))(PanelScreen);
