import * as realTime from "./realtime";
import * as exchange from "../actions/cabinet/exchange";
import * as toasts from "../actions/toasts";
import * as utils from "../utils";
import store from "../store";

let markets = {};

class Exchange {
  constructor(market) {
    this.market = market;

    this.listeners = [
      ["new_orders", this.__orderBookDidUpdated],
      ["order_book", this.__orderBookInit],
      ["orders_filled", this.__orderBookDidUpdated],
      ["order_failed", this.__orderDidFailed],
      ["order_completed", this.__orderDidCompleted],
      ["cancel_order", this.__orderDidCancel],
      ["cancel_orders", this.__ordersDidCancel],
      ["order_cancelled", this.__orderDidCancelled],
      ["cancel_order_failed", this.__orderDidCancelFailed],
      ["order_created", this.__orderDidCreated],
      ["trade_list", this.__orderDidTrade],
      ["balance_update", this.__balanceDidUpdate],
      ["ticker", this.__tickerUpdate],
      ["error_connection", this.__errorConnection],
      ["close_connection", this.__closeConnection],
      ["open_connection", this.__openConnection],
      ["completed_orders", this.__orderBookRemoveOrder]
    ];

    this.__bind();
  }

  destroy() {
    realTime.shared.unsubscribe("market_" + this.market);

    for (let [name, callback] of this.listeners) {
      realTime.shared.removeListener(name, callback);
    }
  }

  __bind() {
    realTime.shared.subscribe("market_" + this.market);

    for (let [name, callback] of this.listeners) {
      realTime.shared.addListener(name, callback);
    }
  }

  __openConnection = () => {
    exchange.load(this.market)(store.dispatch, store.getState); // store HACK
  };

  __closeConnection = () => {
    // unbind(this.market);
    exchange.setStatus("disconnected");
  };

  __errorConnection = () => {
    // unbind(this.market);
    exchange.setStatus("disconnected");
  };

  __orderBookDidUpdated = orders => exchange.orderBookUpdateOrders(orders);
  __orderBookInit = payload => exchange.orderBookInit(payload);

  __orderDidFailed = body => {
    // exchange.setOrderStatus(body.order_id, 'failed');
    exchange.orderFailed(body.order_id);
    toasts.error(utils.getLang("exchange_toastOrderFailed"));
  };

  __orderDidCompleted = body => {
    // exchange.setOrderStatus(body.order.id, 'completed');
    exchange.orderCompleted(body.order);
    exchange.removeOrders([body.order.id]);
    toasts.success(utils.getLang("exchange_toastOrderCompleted"));
  };

  __orderDidCancel = orderId => exchange.removeOrders([orderId]);
  __ordersDidCancel = orderId => exchange.removeOrders(orderId);

  __orderDidCancelled = orderId => {
    exchange.setOrderStatus(orderId, "cancelled");
    toasts.success(utils.getLang("exchange_toastOrderCanceled"));
  };

  __orderDidCancelFailed = orderId => {
    exchange.setOrderPending(orderId, false);
    toasts.error("Can't cancel order");
  };

  __orderDidCreated = order => {
    exchange.addOpenOrder(order);
    toasts.success(utils.getLang("exchange_toastOrderCreated"));
  };

  __orderBookRemoveOrder = orders => {
    exchange.orderBookRemoveOrders(orders);
  };

  __orderDidTrade = orders => {
    exchange.addTrades(orders);
  };

  __balanceDidUpdate = ({ currency, amount }) =>
    exchange.updateBalance(currency, amount);

  __tickerUpdate = ticker => exchange.tickerUpdate(ticker);
}

export function bind(market) {
  markets[market] = new Exchange(market);
}

export function unbind(market) {
  if (markets[market]) {
    markets[market].destroy();
    delete markets[market];
  }
}
