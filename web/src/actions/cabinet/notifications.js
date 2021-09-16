import * as actionTypes from "../actionTypes";

export const loadNotifications = () => ({
  type: actionTypes.NOTIFICATIONS_LOAD_MORE
});

export const setNotificationsLoadingStatus = value => ({
  type: actionTypes.NOTIFICATIONS_SET_LOADING,
  payload: value
});

export const notificationsAddItems = value => ({
  type: actionTypes.NOTIFICATIONS_ADD_ITEMS,
  payload: value
});

export const notificationMarkAsRead = value => ({
  type: actionTypes.NOTIFICATION_MARK_AS_READ,
  payload: value
});
