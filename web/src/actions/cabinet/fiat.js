import * as api from "../../services/api";
import apiSchema from "../../services/apiSchema";
import * as actionTypes from "../actionTypes";
import * as toast from "../toasts";
import { getLang } from "../../utils";
import { closeModal } from "../index";
import { PAGE_COUNT } from "../../index/constants/cabinet";

export function getFiatWallets() {
  return dispatch => {
    api
      .call(apiSchema.Fiat_wallet.DefaultGet)
      .then(payload => {
        dispatch({ type: actionTypes.FIAT_WALLETS_SET, payload });
      })
      .finally(() => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "default",
          status: ""
        });
      });
  };
}

export function getHistoryMore() {
  return (dispatch, getState) => {
    const { history } = getState().fiat;
    dispatch({
      type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
      section: "history",
      status: "loading"
    });
    api
      .call(apiSchema.History.DefaultGet, {
        count: PAGE_COUNT,
        operations: ["withdrawal", "income", "refill", "swap"].join(","),
        start_from: history.next
      })
      .then(payload => {
        dispatch({ type: actionTypes.FIAT_HISTORY_ADD_ITEMS, payload });
      })
      .finally(() => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "history",
          status: ""
        });
      });
  };
}

export function getMerchant(type) {
  return (dispatch, getState) => {
    const apiMethod =
      type === "withdrawal"
        ? apiSchema.Fiat_wallet.WithdrawMethodsGet
        : apiSchema.Fiat_wallet.RefillMethodsGet;

    dispatch({
      type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
      section: "merchants",
      status: "loading"
    });
    api
      .call(apiMethod)
      .then(({ methods }) => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_MERCHANTS,
          merchantType: type,
          methods
        });
      })
      .finally(() => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "merchants",
          status: ""
        });
      });
  };
}

export function clearMerchants() {
  return dispatch => {
    dispatch({ type: actionTypes.FIAT_WALLETS_SET_MERCHANTS, methods: [] });
  };
}

export function exchange({ from, to, amount, amountType }) {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
      section: "marketForm",
      status: "loading"
    });
    api
      .call(apiSchema.Fiat_wallet.ExchangePost, {
        from_currency: from,
        to_currency: to,
        amount_type: amountType,
        amount: amount
      })
      .then(payload => {
        dispatch({ type: actionTypes.FIAT_WALLETS_UPDATE, payload });
        toast.success(getLang("cabinet_fiatWalletExchangeSuccessText"));
      })
      .catch(err => {
        toast.error(err.message);
      })
      .finally(() => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "marketForm",
          status: ""
        });
      });
  };
}

export function payForm({ merchant, amount, currency }) {
  return api
    .call(apiSchema.Fiat_wallet.PayFormGet, {
      merchant,
      amount,
      currency
    })
    .catch(err => {
      toast.error(err.message);
      throw err;
    });
}

export function getRate({ base, currency, type }) {
  const newMarket = [base, currency].join("_");
  return (dispatch, getState) => {
    const { newRate } = getState().fiat.loadingStatus;
    if (type === "rate" && newRate) {
      return false;
    }
    dispatch({
      type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
      section: type,
      status: "loading"
    });
    dispatch({
      type: actionTypes.FIAT_WALLETS_SET_MARKET_EXCHANGE,
      section: type,
      payload: newMarket
    });
    api
      .call(apiSchema.Fiat_wallet.RateGet, { base, currency })
      .then(({ rate }) => {
        const {
          loadingStatus: { newRate },
          market
        } = getState().fiat;

        if ((type === "rate" && newRate) || (market && newMarket !== market)) {
          return false;
        }
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_RATE,
          uprateTime: new Date().getTime(),
          rate
        });
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: type,
          status: null
        });
      })
      .catch(() => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: type,
          status: null
        });
      });
  };
}

export function withdrawalBanksGet() {
  return dispatch => {
    dispatch({
      type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
      section: "withdrawalBankList",
      status: "loading"
    });
    api
      .call(apiSchema.Fiat_wallet.Xendit.WithdrawalBanksGet)
      .then(banks => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_WITHDRAWAL_BANK_LIST,
          banks
        });
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "withdrawalBankList",
          status: ""
        });
      })
      .catch(() => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "withdrawalBankList",
          status: "failed"
        });
      });
  };
}

export function refillBanksGet() {
  return dispatch => {
    dispatch({
      type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
      section: "refillBankList",
      status: "loading"
    }); // TODO LEGACY
    dispatch({
      type: actionTypes.WALLET_SET_STATUS,
      section: "refillBankList",
      status: "loading"
    });
    api
      .call(apiSchema.Fiat_wallet.Xendit.RefillBanksGet)
      .then(banks => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_REFILL_BANK_LIST,
          banks
        }); // TODO LEGACY
        dispatch({
          type: actionTypes.WALLET_SET_REFILL_BANK_LIST,
          banks
        });
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "refillBankList",
          status: ""
        }); // TODO LEGACY
        dispatch({
          type: actionTypes.WALLET_SET_STATUS,
          section: "refillBankList",
          status: ""
        });
      })
      .catch(() => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "refillBankList",
          status: "failed"
        }); // TODO LEGACY
        dispatch({
          type: actionTypes.WALLET_SET_STATUS,
          section: "refillBankList",
          status: "failed"
        });
      });
  };
}

export function fiatWithdrawal(params) {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
      section: "withdrawal",
      status: "loading"
    });
    api
      .call(apiSchema.Fiat_wallet.WithdrawPut, {
        bank_code: params.bank.code,
        account_holder_name: params.accountHolderName,
        account_number: params.accountNumber,
        amount: params.amount,
        email_to: params.email,
        balance_id: params.balance.id,
        ga_code: params.gaCode
      })
      .then(payload => {
        const { transaction } = payload;
        dispatch({
          type: actionTypes.FIAT_WALLETS_APPEND_TRANSACTION,
          transaction
        });
        dispatch({ type: actionTypes.FIAT_WALLETS_UPDATE, payload });
        dispatch({ type: actionTypes.WALLET_UPDATE, payload });
        closeModal();
        toast.success(getLang("cabinet_FiatRefillModal_WithdrawalCreated"));
      })
      .finally(() => {
        dispatch({
          type: actionTypes.FIAT_WALLETS_SET_LOADING_STATUS,
          section: "withdrawal",
          status: null
        });
      })
      .catch(err => {
        toast.error(err.message);
      });
  };
}
