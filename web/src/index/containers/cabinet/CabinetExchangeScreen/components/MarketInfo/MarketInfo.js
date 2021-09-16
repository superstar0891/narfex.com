import "./MarketInfo.less";

import React, { memo } from "react";
import { connect } from "react-redux";
import SVG from "react-inlinesvg";
import { Helmet } from "react-helmet";
// import moment from 'moment/min/moment-with-locales';

import * as UI from "../../../../../../ui";
import * as utils from "../../../../../../utils";
import * as actions from "../../../../../../actions";
import * as exchangeActions from "../../../../../../actions/cabinet/exchange";
import company from "../../../../../constants/company";

class MarketInfo extends React.Component {
  __handleChooseMarket() {
    actions.openModal("choose_market");
  }

  render() {
    const [primary, secondary] = this.props.market.toUpperCase().split("/");
    const price = utils.formatDouble(
      this.props.ticker.price,
      utils.isFiat(secondary) ? 2 : undefined
    );

    return (
      <div className="MarketInfo">
        <Helmet>
          <title>
            {[price, this.props.market.toUpperCase(), company.name].join(" | ")}
          </title>
        </Helmet>
        <div className="MarketInfo__row pair">
          <div className="MarketInfo__pair" onClick={this.__handleChooseMarket}>
            <div className="MarketInfo__pair__primary">{primary}</div>
            <div className="MarketInfo__pair__secondary">{secondary}</div>
          </div>
          <UI.Button
            size="ultra_small"
            type="secondary"
            onClick={this.__handleChooseMarket}
          >
            {utils.getLang("exchange_choosePair")}
          </UI.Button>
        </div>
        {this.__renderPrice()}
        {this.__renderSummary()}
      </div>
    );
  }

  __renderPrice() {
    const { ticker, market } = this.props;
    const [, secondary] = market.split("/");

    const type = ticker.price < ticker.prevPrice ? "down" : "up";

    return (
      <div className="MarketInfo__row price">
        <div className="MarketInfo__info_row">
          <div className="MarketInfo__info_row__label">
            {utils.getLang("exchange_lastPrice")}
          </div>
          <div className="MarketInfo__info_row__value">
            <div className="MarketInfo__info_row__value__primary">
              <UI.NumberFormat
                market
                type={type}
                indicator
                symbol
                number={ticker.price}
                currency={secondary}
                hiddenCurrency
              />
            </div>
            $
            <UI.NumberFormat
              market
              number={ticker.usd_price}
              currency={"usd"}
              hiddenCurrency
            />
          </div>
        </div>
        <div className="MarketInfo__info_row">
          <div className="MarketInfo__info_row__label">
            {utils.getLang("exchange_24h_change")}
          </div>
          <div className="MarketInfo__info_row__value">
            <div className="MarketInfo__info_row__value__primary">
              <UI.NumberFormat
                market
                number={ticker.percent}
                symbol
                type="auto"
                indicator
                percent
                fractionDigits={2}
              />
            </div>
            <UI.NumberFormat
              market
              number={ticker.diff}
              currency={secondary}
              hiddenCurrency
            />
          </div>
        </div>
      </div>
    );
  }

  __renderSummary() {
    const { ticker, market, orderBook } = this.props;
    const [, secondary] = market.split("/");

    const timeFrames = [
      { label: "5 min", value: 5 },
      { label: "15 min", value: 15 },
      { label: "30 min", value: 30 },
      { label: "1 hour", value: 60 },
      { label: "4 hours", value: 240 },
      { label: "1 day", value: "1D" },
      { label: "1 week", value: "1W" }
    ].map(item => {
      return (
        <UI.Button
          key={item.value}
          size="ultra_small"
          type={
            item.value === this.props.chartTimeFrame ? "normal" : "secondary"
          }
          onClick={() => this.props.changeTimeFrame(item.value)}
        >
          {item.label}
        </UI.Button>
      );
    });

    let bestAsk = Math.min(
      ...orderBook.filter(o => o.action === "sell").map(o => o.price)
    );
    let bestBid = Math.max(
      ...orderBook.filter(o => o.action === "buy").map(o => o.price)
    );

    return (
      <div className="MarketInfo__row summary">
        <div className="MarketInfo__summary_line">
          <div className="MarketInfo__info_row">
            <div className="MarketInfo__info_row__label">
              {utils.getLang("exchange_24h_volume")}
            </div>
            <div className="MarketInfo__info_row__value">
              <UI.NumberFormat
                market
                number={ticker.usd_volume}
                currency={"usd"}
                hiddenCurrency
              />
            </div>
          </div>
          <div className="MarketInfo__info_row">
            <div className="MarketInfo__info_row__label">
              {utils.getLang("exchange_24h_high")}
            </div>
            <div className="MarketInfo__info_row__value">
              <UI.NumberFormat
                market
                number={ticker.max}
                currency={secondary}
                hiddenCurrency
              />
            </div>
          </div>
          <div className="MarketInfo__info_row">
            <div className="MarketInfo__info_row__label">
              {utils.getLang("exchange_24h_low")}
            </div>
            <div className="MarketInfo__info_row__value">
              <UI.NumberFormat
                market
                number={ticker.min}
                currency={secondary}
                hiddenCurrency
              />
            </div>
          </div>
          <div className="MarketInfo__info_row">
            <div className="MarketInfo__info_row__label">
              {utils.getLang("exchange_ask")}
            </div>
            <div className="MarketInfo__info_row__value">
              <UI.NumberFormat
                market
                number={bestAsk ? bestAsk : 0}
                currency={secondary}
                hiddenCurrency
              />
            </div>
          </div>
          <div className="MarketInfo__info_row">
            <div className="MarketInfo__info_row__label">
              {utils.getLang("exchange_bid")}
            </div>
            <div className="MarketInfo__info_row__value">
              <UI.NumberFormat
                market
                number={bestBid ? bestBid : 0}
                currency={secondary}
                hiddenCurrency
              />
            </div>
          </div>
        </div>
        <div className="MarketInfo__summary_controls">
          {timeFrames}
          <UI.Button
            size="ultra_small"
            onClick={() => {
              exchangeActions.setFullscreen();
            }}
            className="MarketInfo__fullscreen_button"
            type="secondary"
            title="FullScreen"
          >
            <SVG src={require("../../../../../../asset/16px/fullscreen.svg")} />
          </UI.Button>
        </div>
      </div>
    );
  }
}

export default connect(
  state => ({
    ticker: state.exchange.ticker,
    chartTimeFrame: state.exchange.chartTimeFrame,
    market: state.exchange.market,
    orderBook: state.exchange.orderBook,
    currentLang: state.default.currentLang
  }),
  {
    changeTimeFrame: exchangeActions.changeTimeFrame
  }
)(memo(MarketInfo));
