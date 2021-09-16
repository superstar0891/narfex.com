import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import apiSchema from "../../services/apiSchema";
import store from "../../store";
import * as toast from "../toasts";
import * as exchangeService from "../../services/exchange";

export function load(market) {
  return (dispatch, getState) => {
    const { loadingStatus } = getState().exchange;
    dispatch({
      type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
      section: "default",
      status: loadingStatus.default === "disconnected" ? "reloading" : "loading"
    });

    api
      .call(apiSchema.Exchange.DefaultGet, {
        market,
        chart_time_frame: getState().exchange.chartTimeFrame
      })
      .then(resp => {
        dispatch({
          type: actionTypes.EXCHANGE_SET,
          ...resp,
          marketConfig: resp.market.config,
          market
        });
        dispatch({
          type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
          section: "default",
          status: ""
        });
      })
      .catch(() => {
        dispatch({
          type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
          section: "default",
          status: "failed"
        });
      });
  };
}

export function setStatus(status) {
  store.dispatch({
    type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
    section: "default",
    status
  });
}

export function chooseMarket(market) {
  return (dispatch, getState) => {
    exchangeService.unbind(getState().exchange.market);
    exchangeService.bind(market);
    load(market)(dispatch, getState);
  };
}

export function orderCreate(params) {
  return dispatch => {
    dispatch({
      type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
      section: params.action,
      status: "loading"
    });
    api
      .call(apiSchema.Exchange.OrderPut, params)
      .then(({ balance }) => {
        if (params.type !== "market") {
          dispatch({ type: actionTypes.EXCHANGE_UPDATE_BALANCE, ...balance });
        }
      })
      .catch(err => {
        toast.error(err.message);
      })
      .finally(() => {
        dispatch({
          type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
          section: params.action,
          status: ""
        });
      });
  };
}

export function tradeFormSetType(type) {
  return dispatch => {
    dispatch({
      type: actionTypes.EXCHANGE_TRADING_FORM_SET_TYPE,
      payload: type
    });
  };
}

export function tradeFormSetProperties(type, properties) {
  return dispatch => {
    dispatch({
      type: actionTypes.EXCHANGE_TRADING_FORM_SET_PROPERTIES,
      tradeType: type,
      properties
    });
  };
}

export function orderDelete(orderId) {
  store.dispatch({
    type: actionTypes.EXCHANGE_SET_ORDER_PENDING,
    orderId,
    value: true
  });
  return api
    .call(apiSchema.Exchange.OrderDelete, {
      order_id: orderId
    })
    .catch(() => {
      store.dispatch({
        type: actionTypes.EXCHANGE_SET_ORDER_PENDING,
        orderId,
        value: false
      });
    });
}

export function setOrderPending(orderId, value) {
  store.dispatch({
    type: actionTypes.EXCHANGE_SET_ORDER_PENDING,
    orderId,
    value
  });
}

export function getMarkets() {
  store.dispatch({
    type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
    section: "getMarkets",
    status: "loading"
  });
  return api
    .call(apiSchema.Exchange.MarketsGet)
    .then(({ markets }) => {
      store.dispatch({ type: actionTypes.EXCHANGE_SET_MARKETS, markets });
    })
    .then(() => {
      store.dispatch({
        type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
        section: "getMarkets",
        status: ""
      });
    })
    .catch(() => {
      store.dispatch({
        type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
        section: "getMarkets",
        status: "failed"
      });
    });
}

export function removeOrders(orderIds) {
  store.dispatch({ type: actionTypes.EXCHANGE_REMOVE_ORDERS, orderIds });
}

export function orderBookSelectOrder(order) {
  return dispatch => {
    dispatch({ type: actionTypes.EXCHANGE_ORDER_BOOK_SELECT_ORDER, order });
  };
}

export function moreOrderHistory() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
      section: "lastOrders",
      status: "loading"
    });
    api
      .call(apiSchema.Exchange.OrdersHistoryGet, {
        start_from: getState().exchange.last_orders.next_from
      })
      .then(payload => {
        dispatch({ type: actionTypes.EXCHANGE_ADD_ORDER_HISTORY, payload });
      })
      .catch(err => {
        toast.error(err.message);
      })
      .finally(() => {
        dispatch({
          type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
          section: "lastOrders",
          status: ""
        });
      });
  };
}

export function orderBookInit(payload) {
  store.dispatch({ type: actionTypes.EXCHANGE_ORDER_BOOK_INIT, ...payload });
  store.dispatch({
    type: actionTypes.EXCHANGE_SET_LOADING_STATUS,
    section: "orderBook",
    status: ""
  });
}

export function orderBookUpdateOrders(orders) {
  const [primaryCoin, secondaryCoin] = store
    .getState()
    .exchange.market.split("/");

  store.dispatch({
    type: actionTypes.EXCHANGE_ORDER_BOOK_UPDATE,
    orders: orders.filter(
      order =>
        order.primary_coin === primaryCoin &&
        order.secondary_coin === secondaryCoin
    )
  });
}

export function tickerUpdate(ticker) {
  store.dispatch({ type: actionTypes.EXCHANGE_TICKER_UPDATE, ticker });
}

export function orderCompleted(order) {
  store.dispatch({
    type: actionTypes.EXCHANGE_ORDER_COMPLETED,
    order: {
      ...order,
      updated_at: Math.ceil(Date.now() / 1000)
    }
  });
}

export function orderFailed(orderId) {
  store.dispatch({ type: actionTypes.EXCHANGE_ORDER_FAILED, orderId });
}

export function setOrderStatus(orderId, status) {
  store.dispatch({
    type: actionTypes.EXCHANGE_SET_ORDER_STATUS,
    orderId,
    status
  });
}

export function addOpenOrder(order) {
  store.dispatch({
    type: actionTypes.EXCHANGE_ADD_OPEN_ORDER,
    order: {
      ...order,
      updated_at: Math.ceil(Date.now() / 1000)
    }
  });
}

export function orderBookRemoveOrders(orders) {
  store.dispatch({
    type: actionTypes.EXCHANGE_ORDER_BOOK_REMOVE_ORDER,
    orders
  });
}

export function addTrades(trades) {
  store.dispatch({ type: actionTypes.EXCHANGE_ADD_TRADES, trades });
}

export function updateBalance(currency, amount) {
  store.dispatch({
    type: actionTypes.EXCHANGE_UPDATE_BALANCE,
    currency,
    amount
  });
}

export function setFullscreen(status = true) {
  store.dispatch({ type: actionTypes.EXCHANGE_SET_FULLSCREEN, status });
}

export function changeTimeFrame(timeFrame) {
  return dispatch => {
    dispatch({ type: actionTypes.EXCHANGE_CHANGE_TIME_FRAME, timeFrame });
  };
}
