import * as actions from "../index";
import store from "../../store";
import router from "../../router";
import * as pages from "../../index/constants/pages";

export const singUp = () => {
  const user = store.getState().default.profile.user;
  if (user) {
    router.navigate(pages.CABINET);
  } else {
    actions.openModal("registration");
  }
};

export const swap = () => {
  // TODO Set swap state
  const user = store.getState().default.profile.user;
  if (user) {
    router.navigate(pages.WALLET_SWAP);
  } else {
    actions.openModal("registration");
  }
};

export const buyToken = () => {
  const user = store.getState().default.profile.user;
  if (user) {
    router.navigate(pages.FNDR);
  } else {
    actions.openModal("registration");
  }
};
