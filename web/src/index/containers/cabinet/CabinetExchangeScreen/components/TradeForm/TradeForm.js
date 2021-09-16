import "./TradeForm.less";

import React from "react";
import { connect } from "react-redux";
import * as UI from "../../../../../../ui";
import * as actions from "src/actions/cabinet/exchange";
import { openModal } from "src/actions/index";
import * as utils from "../../../../../../utils";

class TradeForm extends React.Component {
  numberFormat = (number, type) => {
    const { decimals } = this.props.marketConfig[
      type === "primary" ? "primary_coin" : "secondary_coin"
    ];
    return utils.formatDouble(number, decimals);
  };

  componentWillUnmount() {
    this.reset();
  }

  reset = () => {
    ["sell", "buy"].forEach(action => {
      this.props.tradeFormSetProperties(action, { touched: false });
    });
  };

  handleChangeOrderType = orderType => {
    this.props.tradeFormSetType(orderType);
    this.reset();
  };

  handleOrderCreate = action => () => {
    this.props.tradeFormSetProperties(action, {
      touched: true
    });

    const form = this.props.form[action];
    if (form.amount && (this.props.form.type === "market" || form.price)) {
      this.props.orderCreate({
        action,
        type: this.props.form.type,
        market: this.props.market,
        amount: form.amount,
        ...(this.props.form.type === "limit" && { price: form.price })
      });
    }
  };

  handleChangePrice = type => value => {
    const form = this.props.form[type];
    if (!form.amount && form.total) {
      this.props.tradeFormSetProperties(type, {
        price: value,
        amount: 1
      });
    } else {
      this.props.tradeFormSetProperties(type, {
        price: value,
        total: this.numberFormat(value * form.amount, "secondary") || form.total
      });
    }
  };

  handleChangeAmount = type => value => {
    const form = this.props.form[type];
    this.props.tradeFormSetProperties(type, {
      amount: value,
      total: this.numberFormat(value * form.price, "secondary") || ""
    });
  };

  handleChangeTotal = type => value => {
    const { price, amount } = this.props.form[type];
    this.props.tradeFormSetProperties(type, {
      total: value,
      price:
        value && !price
          ? amount
            ? this.numberFormat(value / amount, "secondary")
            : ""
          : price,
      amount:
        value && price ? this.numberFormat(value / price, "primary") : amount
    });
  };

  calcFee = amount => {
    return (amount / 100) * this.props.fee;
  };

  getBalance = currency => {
    return (
      this.props.balances.find(
        b => b.currency.toLowerCase() === currency.toLowerCase()
      ) || {}
    );
  };

  renderForm = type => {
    const isMarket = this.props.form.type === "market";
    const form = this.props.form[type];
    const [
      primaryCurrency,
      secondaryCurrency
    ] = this.props.market.toUpperCase().split("/");
    const balance = this.props.isLogged
      ? this.getBalance(type === "buy" ? secondaryCurrency : primaryCurrency)
      : {};
    const tickerPrice = utils.formatDouble(
      this.props.ticker.price,
      utils.isFiat(secondaryCurrency) ? 2 : undefined
    );
    const marketTotalPrice = utils.formatDouble(
      form.amount * this.props.ticker.price,
      utils.isFiat(secondaryCurrency) ? 2 : undefined
    );

    return (
      <div className="TradeForm__form" key={type}>
        <div className="TradeForm__form__header">
          <div className="TradeForm__form__title">
            {utils.getLang(["global", type].join("_"))}{" "}
            {primaryCurrency.toUpperCase()}
          </div>
          <div className="TradeForm__form__balance">
            <span className="TradeForm__form__fee__label">
              {utils.getLang("global_balance")}:
            </span>
            <UI.NumberFormat
              market
              number={balance.amount}
              currency={balance.currency}
            />
          </div>
        </div>
        <div className="TradeForm__form__row">
          <div className="TradeForm__form__coll">
            <UI.Input
              type={isMarket ? "text" : "number"}
              error={form.touched && !isMarket && !form.price}
              value={!isMarket ? form.price : "~" + tickerPrice}
              onTextChange={this.handleChangePrice(type)}
              size="small"
              placeholder={
                isMarket
                  ? utils.getLang("exchange_type_market", true)
                  : utils.getLang("global_price", true)
              }
              disabled={isMarket}
              indicator={secondaryCurrency}
            />
          </div>
          <div className="TradeForm__form__coll">
            <UI.Input
              type="number"
              error={form.touched && !form.amount}
              value={form.amount}
              onTextChange={this.handleChangeAmount(type)}
              size="small"
              placeholder={utils.getLang("exchange_amount", true)}
              indicator={primaryCurrency}
            />
          </div>
        </div>
        <div className="TradeForm__form__row">
          <div className="TradeForm__form__coll">
            <UI.SwitchTabs
              onChange={value => {
                let amount =
                  ((balance.amount - this.calcFee(balance.amount)) / 100) *
                  value;
                if (type === "sell") {
                  this.handleChangeAmount(type)(utils.formatDouble(amount));
                } else {
                  this.handleChangeTotal(type)(
                    utils.formatDouble(
                      amount,
                      utils.isFiat(secondaryCurrency) ? 2 : undefined
                    )
                  );
                }
              }}
              size="ultra_small"
              disabled={!balance.amount}
              tabs={[25, 50, 75, 100].map(value => ({
                value,
                label: value + "%"
              }))}
            />
          </div>
          <div className="TradeForm__form__coll">
            <UI.Input
              type={isMarket ? "text" : "number"}
              error={form.touched && !isMarket && !form.total}
              value={
                isMarket
                  ? marketTotalPrice
                    ? "~" + marketTotalPrice
                    : ""
                  : form.total
              }
              onTextChange={this.handleChangeTotal(type)}
              size="small"
              disabled={isMarket}
              placeholder={
                isMarket
                  ? utils.getLang("exchange_type_market", true)
                  : utils.getLang("global_total", true)
              }
              indicator={secondaryCurrency}
            />
          </div>
        </div>
        <div className="TradeForm__form__row">
          <div className="TradeForm__form__coll fee">
            <div className="TradeForm__form__fee">
              {utils.getLang("global_fee")}:{" "}
              <UI.NumberFormat market number={this.props.fee} percent />
            </div>
          </div>
          <div className="TradeForm__form__coll">
            <UI.Button
              type={type === "sell" ? "negative" : "green"}
              onClick={this.handleOrderCreate(type)}
              state={this.props.loadingStatus[type]}
              children={
                <>
                  {utils.getLang("global_" + type)} {primaryCurrency}
                </>
              }
              size="small"
            />
          </div>
        </div>
      </div>
    );
  };

  renderPlaceholder() {
    if (this.props.isLogged) return null;
    return (
      <div className="TradeForm__placeholder">
        <div className="TradeForm__placeholder__wrapper">
          <UI.Button onClick={() => openModal("registration")} size="small">
            {utils.getLang("site__authModalSignUpBtn")}
          </UI.Button>
          <span className="TradeForm__placeholder__or">
            {utils.getLang("global_or")}
          </span>
          <UI.Button
            onClick={() => openModal("login")}
            size="small"
            type="secondary"
          >
            {utils.getLang("site__authModalLogInBtn")}
          </UI.Button>
        </div>
      </div>
    );
  }

  render() {
    return (
      <UI.ContentBox className="TradeForm">
        {this.renderPlaceholder()}
        <div className="TradeForm__tradeTypeButtons">
          {["limit", "market"].map(type => (
            <UI.Button
              key={type}
              size="ultra_small"
              onClick={() => this.handleChangeOrderType(type)}
              type={type !== this.props.form.type ? "secondary" : undefined}
            >
              {utils.ucfirst(type)}
            </UI.Button>
          ))}
        </div>
        <div className="TradeForm__forms">
          {["buy", "sell"].map(this.renderForm)}
        </div>
      </UI.ContentBox>
    );
  }
}

export default connect(
  state => ({
    form: state.exchange.form,
    ticker: state.exchange.ticker,
    market: state.exchange.market,
    marketConfig: state.exchange.marketConfig,
    balances: state.exchange.balances,
    loadingStatus: state.exchange.loadingStatus,
    fee: state.exchange.fee,
    isLogged: !!state.default.profile.user,
    currentLang: state.default.currentLang
  }),
  {
    orderCreate: actions.orderCreate,
    tradeFormSetType: actions.tradeFormSetType,
    tradeFormSetProperties: actions.tradeFormSetProperties
  }
)(TradeForm);
