import "./ChooseMarketModal.less";

import React from "react";
import { connect } from "react-redux";
import SVG from "react-inlinesvg";

import * as UI from "../../../../ui";
import * as exchange from "../../../../actions/cabinet/exchange";
import * as actions from "../../../../actions";
import ChartSimple from "../Chart/ChartSimple";
import ModalState from "../ModalState/ModalState";
import router from "../../../../router";
import * as exchangeActions from "src/actions/cabinet/exchange";
import * as PAGES from "../../../constants/pages";
import { getLang } from "src/utils/index";

class ChooseMarketModal extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      currencies: {
        btc: false,
        eth: false,
        ltc: false,
        bch: false,
        usdt: false
      },
      search: ""
    };
  }

  componentDidMount() {
    exchange.getMarkets();
  }

  handleToggleCurrency(currency) {
    this.setState({
      currencies: {
        ...this.state.currencies,
        [currency]: !this.state.currencies[currency]
      }
    });
  }

  shouldComponentUpdate(nextProps, nextState, nextContext) {
    return (
      this.props.status !== nextProps.status ||
      this.props.markets !== nextProps.markets ||
      JSON.stringify(this.state) !== JSON.stringify(nextState)
    );
  }

  __handleChooseMarket(market) {
    market = market.toLowerCase();
    this.props.chooseMarket(market);
    router.navigate(PAGES.EXCHANGE, { market: market.replace("/", "_") });
    // this.props.onClose();
  }

  render() {
    if (this.props.status) {
      return <ModalState status={this.props.status} onRetry={() => {}} />;
    }

    const { markets } = this.props;
    const currentCurrencies = Object.keys(this.state.currencies).filter(
      key => this.state.currencies[key]
    );
    return (
      <UI.Modal
        className="ChooseMarketModal__wrapper"
        noSpacing
        isOpen={true}
        onClose={this.props.onClose}
      >
        <div className="ChooseMarketModal">
          <div className="ChooseMarketModal__filters">
            <UI.ModalHeader>{getLang("exchange_choosePair")}</UI.ModalHeader>
            <div className="ChooseMarketModal__filters__form">
              <UI.Input
                value={this.state.search}
                onTextChange={value => this.setState({ search: value })}
                placeholder="Search Pairs"
                indicatorWidth={28}
                indicator={
                  <SVG src={require("../../../../asset/24px/search.svg")} />
                }
              />
              <div className="ChooseMarketModal__filters__buttons">
                {Object.keys(this.state.currencies).map(key => (
                  <UI.Button
                    onClick={() => this.handleToggleCurrency(key)}
                    size={this.props.adaptive && "ultra_small"}
                    type={!this.state.currencies[key] ? "secondary" : null}
                  >
                    {key.toUpperCase()}
                  </UI.Button>
                ))}
              </div>
            </div>
          </div>
          <div className="ChooseMarketModal__market_list">
            <UI.Table header={false} inline>
              {markets.map(({ market, ticker, chart }, key) => {
                if (!ticker) ticker = {};
                const [primary, secondary] = market.name
                  .split("/")
                  .map(actions.getCurrencyInfo);

                if (
                  (this.state.search &&
                    ![
                      primary.abbr,
                      secondary.abbr,
                      secondary.name.toLowerCase()
                    ].includes((this.state.search || "").toLowerCase())) ||
                  (currentCurrencies.length &&
                    !currentCurrencies.includes(primary.abbr) &&
                    !currentCurrencies.includes(secondary.abbr))
                ) {
                  return null;
                }

                const series = {
                  color: primary.color,
                  shadow: {
                    color: primary.color
                  },
                  data: chart.map(([x, y]) => ({ x, y }))
                };

                const currencyType = ticker.percent >= 0 ? "up" : "down";

                return (
                  <UI.TableCell
                    key={key}
                    onClick={() => this.__handleChooseMarket(market.name)}
                  >
                    <UI.TableColumn>
                      <div className="ChooseMarketModal__icons">
                        <UI.CircleIcon
                          className="ChooseMarketModal__icon"
                          currency={primary}
                        />
                        <UI.CircleIcon
                          className="ChooseMarketModal__icon"
                          currency={secondary}
                        />
                      </div>
                    </UI.TableColumn>
                    <UI.TableColumn align="left">
                      <div className="ChooseMarketModal__market">
                        <span className="ChooseMarketModal__market_primary">
                          {primary.abbr.toUpperCase()}
                        </span>
                        <span className="ChooseMarketModal__market_secondary">
                          {" "}
                          / {secondary.abbr.toUpperCase()}
                        </span>
                      </div>
                    </UI.TableColumn>
                    <UI.TableColumn className="ChooseMarketModal__chart">
                      {!this.props.adaptive && (
                        <ChartSimple marker={false} series={[series]} />
                      )}
                    </UI.TableColumn>
                    <UI.TableColumn>
                      {ticker && (
                        <UI.NumberFormat
                          fractionDigits={market.config.secondary_coin.decimals}
                          number={ticker.price}
                          type={currencyType}
                          hiddenCurrency
                        />
                      )}
                    </UI.TableColumn>
                    {!this.props.adaptive && (
                      <UI.TableColumn>
                        {ticker && (
                          <UI.NumberFormat
                            number={ticker.usd_price}
                            currency="usd"
                          />
                        )}
                      </UI.TableColumn>
                    )}
                    <UI.TableColumn>
                      {ticker && (
                        <UI.NumberFormat
                          number={ticker.percent}
                          type={currencyType}
                          percent
                        />
                      )}
                    </UI.TableColumn>
                  </UI.TableCell>
                );
              })}
            </UI.Table>
          </div>
        </div>
      </UI.Modal>
    );
  }
}

export default connect(
  state => ({
    adaptive: state.default.adaptive,
    markets: state.exchange.markets,
    status: state.exchange.loadingStatus.getMarkets
  }),
  {
    chooseMarket: exchangeActions.chooseMarket
  }
)(ChooseMarketModal);
