import "./CabinetExchangeScreen.less";

import React from "react";
import { connect } from "react-redux";

import LoadingStatus from "../../../components/cabinet/LoadingStatus/LoadingStatus";
import CabinetBaseScreen from "../CabinetBaseScreen/CabinetBaseScreen";
import SwitchBlock from "./components/SwitchBlock/SwitchBlock";
import Trades from "./components/Trades/Trades";
import Balances from "./components/Balances/Balances";

import * as utils from "../../../../utils";
import OrderBook from "./components/OrderBook/OrderBook";
import TradeForm from "./components/TradeForm/TradeForm";
import Orders from "./components/Orders/Orders";
import MarketInfo from "./components/MarketInfo/MarketInfo";
import MarketInfoAdaptive from "./components/MarketInfoAdaptive/MarketInfoAdaptive";
import Chart from "./components/Chart/Chart";
import * as exchangeService from "../../../../services/exchange";
import * as UI from "../../../../ui/";
import * as exchangeActions from "../../../../actions/cabinet/exchange";
import * as actions from "../../../../actions";
import { Helmet } from "react-helmet";
import COMPANY from "../../../constants/company";

class CabinetExchangeScreen extends CabinetBaseScreen {
  constructor(props) {
    super(props);

    this.state = {
      ordersTab: "open",
      orderBookType: "all"
    };
  }

  componentDidMount() {
    super.componentDidMount();
    this.props.setTitle(utils.getLang("cabinet_header_exchange"));
  }

  componentWillUnmount() {
    exchangeService.unbind(this.props.market);
  }

  render() {
    return (
      <div>
        <Helmet>
          <title>
            {[
              COMPANY.name,
              utils.getLang("cabinet_header_exchange", true)
            ].join(" - ")}
          </title>
        </Helmet>
        {this.__renderContent()}
      </div>
    );
  }

  renderDisconnectedModal() {
    if (!["disconnected", "reloading"].includes(this.loadingStatus))
      return null;
    return (
      <UI.Modal
        skipClose
        className="Exchange__disconnectModal"
        isOpen={true}
        onClose={this.props.onClose}
      >
        <div className="Exchange__disconnectModal__content">
          <p>{utils.getLang("exchange_failedConnect")}</p>
          <LoadingStatus inline status={"loading"} />
          <p>{utils.getLang("exchange_reconnect")}</p>
        </div>
      </UI.Modal>
    );
  }

  __renderContent() {
    if (["loading", "failed"].includes(this.loadingStatus)) {
      return (
        <LoadingStatus
          status={this.loadingStatus}
          onRetry={() => this.load()}
        />
      );
    }

    return this.props.adaptive
      ? this.__renderExchangeAdaptive()
      : this.__renderExchange();
  }

  __renderExchangeAdaptive() {
    return (
      <div className="Exchange__wrapper">
        {this.renderDisconnectedModal()}
        <UI.ContentBox>
          <MarketInfoAdaptive {...this.props.ticker} />
          <Chart
            adaptive={true}
            fullscreen={this.props.fullscreen}
            symbol={this.props.market
              .split("/")
              .join(":")
              .toUpperCase()}
            interval={this.props.chartTimeFrame}
          />
        </UI.ContentBox>
        <TradeForm />
        <OrderBook />
        <Balances />
        <SwitchBlock
          type="buttons"
          contents={[
            {
              title: utils.getLang("exchange_trades"),
              content: <Trades skipWrapper />
            },
            {
              title: utils.getLang("exchange_openOrders"),
              content: <Orders type="open" adaptive={true} />,
              disabled: !this.props.user
            },
            {
              title: utils.getLang("exchange_myTrades"),
              content: <Orders type="history" adaptive={true} />,
              disabled: !this.props.user
            }
          ]}
        />
      </div>
    );
  }

  __renderExchange() {
    return (
      <div className="Exchange__wrapper">
        {this.renderDisconnectedModal()}
        <div className="Exchange__left_content">
          {this.props.user && <Balances />}
          <Trades />
        </div>
        <div className="Exchange__right_content">
          <div className="Exchange__trade_content">
            <div className="Exchange__chart_wrapper">
              <UI.ContentBox className="Exchange__chart">
                {this.props.ticker && <MarketInfo />}
                <Chart
                  fullscreen={this.props.fullscreen}
                  symbol={this.props.market
                    .split("/")
                    .join(":")
                    .toUpperCase()}
                  interval={this.props.chartTimeFrame}
                />
              </UI.ContentBox>
              {this.props.ticker && <TradeForm />}
            </div>
            <div className="Exchange__orderBook">
              <OrderBook />
            </div>
          </div>
          {this.props.user && <Orders />}
        </div>
      </div>
    );
  }

  load() {
    let { market } = this.props.router.route.params;
    market =
      (market && market.toLowerCase().replace("_", "/")) || this.props.market;
    // this.props.load(market);
    exchangeService.bind(market);
  }
}

export default connect(
  state => ({
    ...state.exchange, // TODO не передавать лишнии props
    adaptive: state.default.adaptive,
    router: state.router,
    user: state.default.profile.user,
    translator: state.settings.translator,
    currentLang: state.default.currentLang
  }),
  {
    load: exchangeActions.load,
    chooseMarket: exchangeActions.chooseMarket,
    setTitle: actions.setTitle
  }
)(CabinetExchangeScreen);
