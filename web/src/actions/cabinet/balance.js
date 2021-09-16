import * as api from "../../services/api";
import apiSchema from "../../services/apiSchema";
import * as toast from "../toasts";
import * as utils from "../../utils";
import store from "../../store";
import * as actionTypes from "../actionTypes";

export function getBalance(category) {
  return api.call(apiSchema.Balance.DefaultGet, { category });
}

export function deposit({ from, amount }) {
  return api
    .call(apiSchema.Balance.DepositPost, {
      wallet_id: from,
      amount
    })
    .then(res => {
      store.dispatch({ type: actionTypes.EXCHANGE_UPDATE_BALANCE, ...res });
      toast.success(utils.getLang("cabinet_manageBalance_withdraw_success"));
    })
    .catch(err => {
      toast.error(err.message);
      throw err;
    });
}

export function withdraw({ from, amount }) {
  return api
    .call(apiSchema.Balance.WithdrawPost, {
      balance_id: from,
      amount
    })
    .then(res => {
      store.dispatch({
        type: actionTypes.EXCHANGE_UPDATE_BALANCE,
        ...res.balance
      });
      toast.success(utils.getLang("cabinet_manageBalance_withdraw_success"));
    })
    .catch(err => {
      toast.error(err.message);
      throw err;
    });
}
