// styles
// external
// internal
import store from "../../store";
import apiSchema from "../../services/apiSchema";
import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import * as toastsActions from "../toasts";
import * as utils from "../../utils";
import router from "../../router";
import { PAGE_COUNT } from "../../index/constants/cabinet";

export function loadWallets() {
  return (dispatch, getState) => {
    const { currency } = router.getState().params;

    return new Promise((resolve, reject) => {
      dispatch({
        type: actionTypes.WALLETS_SET_LOADING_STATUS,
        section: "default",
        status: "loading"
      });
      api
        .call(apiSchema.Wallet.DefaultGet, { count: PAGE_COUNT })
        .then(({ balances, transactions, transfers }) => {
          dispatch({
            type: actionTypes.WALLETS_SET,
            wallets: balances,
            currency
          });
          dispatch({
            type: actionTypes.WALLETS_TRANSACTIONS_SET,
            items: transactions
          });
          dispatch({
            type: actionTypes.WALLETS_TRANFERS_SET,
            items: transfers
          });
          dispatch({
            type: actionTypes.WALLETS_SET_LOADING_STATUS,
            section: "default",
            status: null
          });
          resolve(balances);
        })
        .catch(() => {
          toastsActions.toastPush("Error load wallets", "error")(
            dispatch,
            getState
          );
          dispatch({
            type: actionTypes.WALLETS_SET_LOADING_STATUS,
            section: "default",
            status: "failed"
          });
          reject();
        });
    });
  };
}

export function getWallets() {
  return new Promise((resolve, reject) => {
    const state = store.getState().wallets;
    if (state.wallets.length > 0) {
      const { currency } = router.getState().params;
      if (currency)
        store.dispatch({
          type: actionTypes.WALLETS_SET,
          wallets: state.wallets,
          currency
        });
      resolve(state.wallets);
    } else {
      return loadWallets()(store.dispatch)
        .then(wallets => resolve(wallets))
        .catch(() => reject());
    }
  });
}

export function loadMoreTransactions() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.WALLETS_TRANSACTIONS_LOADING_MORE,
      status: "loading"
    });
    api
      .call(apiSchema.Wallet.TransactionsGet, {
        start_from: store.getState().wallets.transactions.next,
        count: PAGE_COUNT
      })
      .then(data => {
        let items = data.items;
        let next = data.next;
        dispatch({
          type: actionTypes.WALLETS_TRANSACTIONS_LOADING_MORE,
          status: ""
        });
        dispatch({
          type: actionTypes.WALLETS_TRANSACTIONS_APPEND,
          items,
          next
        });
      })
      .catch(() => {
        toastsActions.toastPush("Error load more transactions", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.WALLETS_TRANSACTIONS_LOADING_MORE,
          status: "failed"
        });
      });
  };
}

export function loadMoreTransfers() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.WALLETS_TRANFERS_LOADING_MORE,
      status: "loading"
    });
    api
      .call(apiSchema.Wallet.TransfersGet, {
        start_from: store.getState().wallets.transfers.next,
        count: PAGE_COUNT
      })
      .then(data => {
        let items = data.items;
        let next = data.next;
        dispatch({
          type: actionTypes.WALLETS_TRANFERS_LOADING_MORE,
          status: ""
        });
        dispatch({ type: actionTypes.WALLETS_TRANFERS_APPEND, items, next });
      })
      .catch(() => {
        toastsActions.toastPush("Error load more transfers", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.WALLETS_TRANSACTIONS_LOADING_MORE,
          status: "failed"
        });
      });
  };
}

export function getNoGeneratedCurrencies() {
  const state = store.getState();
  return Object.values(state.cabinet.currencies).filter(
    c => c.can_generate && c.is_exists === false
  );
}

export function loadTransactionInfo(id, type) {
  return new Promise((resolve, reject) => {
    api
      .get(`wallet/${type}/${id}`)
      .then(resp => {
        resolve(resp);
      })
      .catch(() => reject());
  });
}

export function generateWallet(currency) {
  return api
    .call(apiSchema.Wallet.GeneratePut, {
      currency
    })
    .then(wallet => {
      store.dispatch({ type: actionTypes.WALLETS_GENERATE_SUCCESS, wallet });
    })
    .catch(error => {
      toastsActions.error(error.message);
      throw error;
    });
}

export function sendCoins(params) {
  return dispatch => {
    const method =
      params.type === "login"
        ? apiSchema.Wallet.TransferSendPut
        : apiSchema.Wallet.TransactionSendPut;
    dispatch({
      type: actionTypes.WALLETS_SET_LOADING_STATUS,
      section: "send",
      status: "loading"
    });
    api
      .call(method, params)
      .then(payload => {
        const { wallet, transaction, transfer } = payload;
        toastsActions.success(utils.getLang("cabinet_sendCoinsModal_success"));
        transaction &&
          dispatch({ type: actionTypes.WALLETS_NEW_TRANSACTION, transaction });
        transfer &&
          dispatch({ type: actionTypes.WALLETS_NEW_TRANSFER, transfer });

        dispatch({
          type: actionTypes.WALLETS_SET_LOADING_STATUS,
          section: "send",
          status: "success"
        });
        dispatch({
          type: actionTypes.WALLETS_SET_LOADING_STATUS,
          section: "send",
          status: null
        });
        dispatch({ type: actionTypes.WALLETS_SEND_COIN_MODAL_CLEAR });
        dispatch({ type: actionTypes.WALLETS_WALLET_UPDATE, wallet }); // TODO LEGACY
        dispatch({ type: actionTypes.WALLET_UPDATE, payload: payload });
      })
      .catch(err => {
        toastsActions.error(err.message);
        dispatch({
          type: actionTypes.WALLETS_SET_LOADING_STATUS,
          section: "send",
          status: null
        });
        dispatch({
          type: actionTypes.WALLETS_SET_LOADING_STATUS,
          section: "sendCode",
          status: err.code
        });
      });
  };
}

export function getLimits() {
  return dispatch => {
    dispatch({
      type: actionTypes.WALLETS_SET_LOADING_STATUS,
      section: "limits",
      status: "loading"
    });
    api
      .call(apiSchema.Wallet.SendGet)
      .then(({ limits }) => {
        dispatch({ type: actionTypes.WALLETS_SET_LIMITS, limits });
      })
      .catch(err => {
        toastsActions.error(err.message);
      })
      .finally(() => {
        dispatch({
          type: actionTypes.WALLETS_SET_LOADING_STATUS,
          section: "limits",
          status: null
        });
      });
  };
}

export function sendCoinModalSetValue(property, value) {
  return dispatch => {
    dispatch({
      type: actionTypes.WALLETS_SEND_COIN_MODAL_SET_VALUE,
      property,
      value
    });
  };
}

export function checkLogin(login) {
  return api
    .call(apiSchema.Profile.CheckLoginPost, { login })
    .then(({ response }) => {
      if (response === "not_found") {
        return Promise.reject();
      } else {
        return Promise.resolve();
      }
    });
}

export function tokenSoldAmountGet() {
  return api.call(apiSchema.Wallet.TokenSoldAmountGet);
}

export function tokenRateGet(currency) {
  return api.call(apiSchema.Wallet.TokenRateGet, { currency });
}

export function buyToken(currency, amount) {
  return api
    .call(apiSchema.Wallet.BuyTokenPost, { currency, amount })
    .then(r => {
      store.dispatch({
        type: actionTypes.WALLETS_WALLET_UPDATE,
        wallet: r.from_wallet
      });
      store.dispatch({
        type: actionTypes.WALLETS_WALLET_UPDATE,
        wallet: r.to_wallet
      });
      toastsActions.success(utils.getLang("global_success"));
    })
    .catch(err => {
      toastsActions.error(err.message);
      throw err;
    });
}
