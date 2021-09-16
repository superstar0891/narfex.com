// styles
// external
// internal
import apiSchema from "../../services/apiSchema";
import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import * as toastsActions from "../toasts";
import { closeModal } from "../index";
import * as utils from "../../utils";
import store from "../../store";
import { PAGE_COUNT } from "../../index/constants/cabinet";

export function loadDashboard() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.PROFILE_SET_LOADING_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Dashboard.DefaultGet)
      .then(dashboard => {
        dispatch({ type: actionTypes.PROFILE_DASHBOARD_SET, dashboard });
        dispatch({
          type: actionTypes.PROFILE_SET_LOADING_STATUS,
          section: "default",
          status: ""
        });
      })
      .catch(err => {
        toastsActions.toastPush("Error load dashboard", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.PROFILE_SET_LOADING_STATUS,
          section: "default",
          status: "failed"
        });
      });
  };
}

export function setVerificationStatus(status) {
  return dispatch => {
    dispatch({ type: actionTypes.PROFILE_SET_VERIFICATION_STATUS, status });
  };
}

export function changeSecretKay(secret) {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.PROFILE_SET_LOADING_STATUS,
      section: "secretKey",
      status: "loading"
    });
    api
      .call(apiSchema.Profile.SecretKeyLoggedPost, { secret })
      .then(dashboard => {
        dispatch({ type: actionTypes.PROFILE_SET_SECRET_SUCCESS });
        toastsActions.toastPush(
          utils.getLang("cabinet_secretKeyChangedSuccessfully"),
          "success"
        )(dispatch, getState);
        closeModal();
      })
      .catch(err => {
        toastsActions.toastPush(err.message, "error")(dispatch, getState);
      })
      .finally(() => {
        dispatch({
          type: actionTypes.PROFILE_SET_LOADING_STATUS,
          section: "secretKey",
          status: ""
        });
      });
  };
}

export function gaInit(code) {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.PROFILE_SET_LOADING_STATUS,
      section: "setGa",
      status: "loading"
    });
    api
      .call(apiSchema.Profile.GaInitPost, { ga_code: code })
      .then(dashboard => {
        dispatch({ type: actionTypes.PROFILE_SET_GA_SUCCESS });
        closeModal();
        toastsActions.toastPush(
          utils.getLang("cabinet_gaCodeChangedSuccessfully"),
          "success"
        )(dispatch, getState);
      })
      .catch(err => {
        throw toastsActions.toastPush(err.message, "error")(dispatch, getState);
      })
      .finally(() => {
        dispatch({
          type: actionTypes.PROFILE_SET_LOADING_STATUS,
          section: "setGa",
          status: ""
        });
      });
  };
}

export function getPartner() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.PROFILE_SET_LOADING_STATUS,
      section: "partners",
      status: "loading"
    });
    api
      .call(apiSchema.Partner.DefaultGet)
      .then(partner => {
        dispatch({ type: actionTypes.PROFILE_PARTNER_SET, partner });
        dispatch({
          type: actionTypes.PROFILE_SET_LOADING_STATUS,
          section: "partners",
          status: ""
        });
      })
      .catch(() => {
        toastsActions.toastPush("Error load partner", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.PROFILE_SET_LOADING_STATUS,
          section: "partners",
          status: "failed"
        });
      });
  };
}

export function userRole(role) {
  const { profile } = store.getState().default;
  return profile.roles && profile.roles.includes(role);
}

export function getPartnerMore() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.PROFILE_SET_LOADING_STATUS,
      section: "partnersTable",
      status: "loading"
    });
    api
      .call(apiSchema.Partner.ClientsGet, {
        start_from: store.getState().profile.partner.clients.next,
        count: PAGE_COUNT
      })
      .then(({ clients: { items, next } }) => {
        dispatch({
          type: actionTypes.PROFILE_SET_LOADING_STATUS,
          section: "partnersTable",
          status: ""
        });
        dispatch({ type: actionTypes.PROFILE_PARTNER_APPEND, items, next });
      })
      .catch(() => {
        toastsActions.toastPush("Error load more partners", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.PROFILE_SET_LOADING_STATUS,
          section: "partnersTable",
          status: "failed"
        });
      });
  };
}

export function saveInviteLink(link, name) {
  return (dispatch, getStore) => {
    dispatch({
      type: actionTypes.PROFILE_INVITE_LINK_UPDATE,
      linkId: link.id,
      name
    });
    api
      .call(apiSchema.Partner.InviteLinkPost, {
        id: link.id,
        name
      })
      .then(() => {
        toastsActions.toastPush("Link name updated", "success")(
          dispatch,
          getStore
        );
      })
      .catch(err => {
        toastsActions.toastPush(err.message, "error")(dispatch, getStore);
        dispatch({
          type: actionTypes.PROFILE_INVITE_LINK_UPDATE,
          linkId: link.id,
          name: link.name
        });
      });
  };
}

export function createInviteLink(name) {
  return dispatch => {
    return new Promise((resolve, reject) => {
      api
        .call(apiSchema.Partner.InviteLinkPut, {
          name
        })
        .then(link => {
          resolve();
          dispatch({ type: actionTypes.PROFILE_INVITE_LINK_ADD, link });
        })
        .catch(err => reject(err));
    });
  };
}

export function deleteInviteLink(linkId) {
  return dispatch => {
    dispatch({ type: actionTypes.PROFILE_INVITE_LINK_DELETE, linkId });
    api
      .call(apiSchema.Partner.InviteLinkDelete, {
        id: linkId
      })
      .catch(() =>
        dispatch({ type: actionTypes.PROFILE_INVITE_LINK_RESTORE, linkId })
      );
  };
}

export function restoreInviteLink(linkId) {
  return dispatch => {
    dispatch({ type: actionTypes.PROFILE_INVITE_LINK_RESTORE, linkId });
    api
      .call(apiSchema.Partner.InviteLinkRestorePost, {
        id: linkId
      })
      .catch(() =>
        dispatch({ type: actionTypes.PROFILE_INVITE_LINK_DELETE, linkId })
      );
  };
}

export function loadPartnerInfo(login) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Partner.PartnerInfoGet, {
        login
      })
      .then(info => resolve(info))
      .catch(err => reject(err));
  });
}

export function getGAHash() {
  return api.call(apiSchema.Profile.GaInitGet);
}

export function getSumsub() {
  return api.call(apiSchema.Sumsub.GetAccessTokenGet);
}

export function inviteAgent(login) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Partner.SendInvitePost, {
        login
      })
      .then(info => resolve(info))
      .catch(err => reject(err));
  });
}
