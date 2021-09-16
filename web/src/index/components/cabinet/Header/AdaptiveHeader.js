import "./Header.less";

import React from "react";
import { connect } from "react-redux";
import InternalNotification from "../InternalNotification/InternalNotification";

class AdaptiveHeader extends React.Component {
  state = { activePage: null };

  render() {
    return (
      <div className="CabinetHeaderContainer">
        <div className="CabinetHeader">
          <div className="CabinetHeader__leftContent">
            <div className="CabinetHeader__leftContent_icon">
              {this.props.leftContent}
            </div>
          </div>
          <div className="CabinetHeader__mainContent">
            <div className="CabinetHeader__mainContent_text">
              <span>{this.props.mainContent.content}</span>
            </div>
          </div>
          <div className="CabinetHeader__rightContent">
            {this.props.rightContent}
          </div>
        </div>
        <InternalNotification />
      </div>
    );
  }
}

AdaptiveHeader.defaultProps = {
  leftContent: "",
  mainContent: {
    type: "logotype",
    content: ""
  },
  rightContent: ""
};

export default connect(
  state => ({
    profile: state.default.profile,
    notifications: state.notifications,
    router: state.router,
    langList: state.default.langList,
    title: state.default.title
  }),
  {
    // loadNotifications: notificationsActions.loadNotifications,
  }
)(AdaptiveHeader);
