// styles
// external
// internal
import store from "../../store";
import apiSchema from "../../services/apiSchema";
import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import * as toastsActions from "../toasts";
import { PAGE_COUNT } from "../../index/constants/cabinet";

export function loadInvestments() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Investment.DefaultGet)
      .then(({ deposits, payments, chart, balances, ...props }) => {
        payments = Object.values(payments);
        dispatch({
          type: actionTypes.INVESTMENTS_SET,
          deposits,
          payments,
          chart,
          balances
        });
        dispatch({
          type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
          section: "default",
          status: ""
        });
      })
      .catch(() => {
        toastsActions.toastPush("Error load investment", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
          section: "default",
          status: "failed"
        });
      });
  };
}

export function loadProfitHistory() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
      section: "profits",
      status: "loading"
    });
    api
      .call(apiSchema.Investment.ProfitGet, {
        count: PAGE_COUNT
      })
      .then(({ profits, total_count }) => {
        dispatch({
          type: actionTypes.INVESTMENTS_PROFITS_SET,
          profits,
          total: total_count
        });
        dispatch({
          type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
          section: "profits",
          status: ""
        });
      })
      .catch(err => {
        toastsActions.toastPush("Error load profit history", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
          section: "profits",
          status: "failed"
        });
      });
  };
}

export function getDeposit(id) {
  return api.call(apiSchema.Investment.DepositGet, {
    deposit_id: id
  });
}

export function depositCalculate(depositId, amount) {
  return api.call(apiSchema.Investment.DepositCalculateGet, {
    deposit_id: depositId,
    amount
  });
}

export function depositWithdraw(params) {
  return api.call(apiSchema.Investment.DepositWithdrawPut, params);
}

export function calculate({ currency, planId, amount, days }) {
  return api.call(apiSchema.Investment.CalculateGet, {
    currency: currency,
    plan_id: planId,
    amount,
    steps: days.map(d => [d.dayNumber, d.amount]).flat()
  });
}

export function loadMoreProfitHistory() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
      section: "profitsAppend",
      status: "loading"
    });
    api
      .call(apiSchema.Investment.ProfitGet, {
        start_from: store.getState().investments.profits.next,
        count: PAGE_COUNT
      })
      .then(({ profits, total_count, next }) => {
        dispatch({
          type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
          section: "profitsAppend",
          status: ""
        });
        dispatch({
          type: actionTypes.INVESTMENTS_PROFITS_APPEND,
          profits,
          next,
          total: total_count
        });
      })
      .catch(err => {
        toastsActions.toastPush("Error load profit history", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
          section: "profitsAppend",
          status: "failed"
        });
      });
  };
}

export function loadWithdrawalHistory() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
      section: "withdrawals",
      status: "loading"
    });
    api
      .call(apiSchema.Investment.WithdrawalGet)
      .then(({ withdrawals, total_count }) => {
        dispatch({
          type: actionTypes.INVESTMENTS_WITHDRAWALS_SET,
          withdrawals,
          total_count
        });
        dispatch({
          type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
          section: "withdrawals",
          status: ""
        });
      })
      .catch(err => {
        toastsActions.toastPush("Error load withdrawal history", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.INVESTMENTS_SET_LOADING_STATUS,
          section: "withdrawals",
          status: "failed"
        });
      });
  };
}

export function loadMoreWithdrawalHistory() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.INVESTMENTS_WITHDRAWALS_SET_LOADING_MORE_STATUS,
      payload: true
    });
    api
      .call(apiSchema.Investment.WithdrawalGet, {
        start_from: store.getState().investments.withdrawals.next,
        count: PAGE_COUNT
      })
      .then(data => {
        const {
          withdrawals: { items, next },
          total_count
        } = data;
        dispatch({
          type: actionTypes.INVESTMENTS_WITHDRAWALS_SET_LOADING_MORE_STATUS,
          payload: false
        });
        dispatch({
          type: actionTypes.INVESTMENTS_WITHDRAWALS_APPEND,
          items,
          next,
          total_count
        });
      })
      .catch(() => {
        toastsActions.toastPush("Error load more withdrawal history", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.INVESTMENTS_WITHDRAWALS_SET_LOADING_MORE_STATUS,
          payload: false
        });
      });
  };
}

export function depositAdd({ amount, wallet_id, plan_id, deposit_type }) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Investment.DepositPut, {
        amount,
        wallet_id,
        plan_id,
        deposit_type
      })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}

export function withdrawAdd({ amount, wallet_id, ga_code }) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Investment.WithdrawPut, { amount, wallet_id, ga_code })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}

export function getWithdraw(currency) {
  return api.call(apiSchema.Investment.WithdrawGet, { currency });
}

export function createDeposit(pool, params) {
  return api
    .call(apiSchema.Investment[pool ? "PoolDepositPut" : "DepositPut"], params)
    .then(({ balances, deposit }) => {
      store.dispatch({
        type: actionTypes.INVESTMENTS_OPEN_DEPOSIT_SUCCESS,
        balances,
        deposit
      });
    })
    .catch(err => {
      toastsActions.error(err.message);
      throw err;
    });
}

export function openDepositModalPropertySet(payload) {
  store.dispatch({
    type: actionTypes.INVESTMENTS_OPEN_DEPOSIT_MODAL_PROPERTY_SET,
    payload
  });
}

export function getPlans(currency, amount, deposit_type) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Investment.PlansGet, { currency, amount, deposit_type })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}
