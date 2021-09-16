import React from "react";
import { connect } from "react-redux";
import url from "url";
import router from "../../../../router";
import * as UI from "../../../../ui";
import * as internalNotifications from "../../../../actions/cabinet/internalNotifications";
import { openModal } from "../../../../actions";
import Lang from "../../../../components/Lang/Lang";
import {
  getAdminToken,
  logoutFromAdminViewMode
} from "../../../../services/auth";

const InternalNotification = props => {
  const { items } = props.internalNotifications;

  const notification = items.length ? items[0] : null;

  if (getAdminToken()) {
    return (
      <UI.InternalNotification
        type="danger"
        acceptText={<Lang name="cabinet_exitActionButton" />}
        message={<Lang name="admin_viewCabinetModeNotification" />}
        onAccept={() => logoutFromAdminViewMode()}
      />
    );
  }

  if (!notification) {
    return null;
  }

  const handleAction = () => {
    if (notification.link) {
      const link = url.parse(notification.link, true);
      router.navigate(
        link.pathname.substr(1),
        link.query,
        notification.params,
        () => {
          props.dropInternalNotifications(notification.type);
        }
      );
    }

    // TODO: Сделать type: Modal вместо google_code и secret_key
    if (notification.type === "google_code") {
      openModal("google_code", {}, {}, () => {
        props.dropInternalNotifications(notification.type);
      });
    }
    if (notification.type === "secret_key") {
      openModal("secret_key", {}, {}, () => {
        props.dropInternalNotifications(notification.type);
      });
    }
  };

  return (
    <UI.InternalNotification
      acceptText={notification.button_text}
      message={notification.caption}
      onAccept={handleAction}
      onClose={() => {
        props.dropInternalNotifications(notification.type);
      }}
    />
  );
};

export default connect(
  state => ({
    internalNotifications: state.internalNotifications
  }),
  {
    dropInternalNotifications: internalNotifications.drop
  }
)(InternalNotification);
