import "./Chart.less";

import React from "react";
import { connect } from "react-redux";
import { widget } from "../../../../../../libs/charting_library/charting_library.min";
import { classNames as cn, ucfirst } from "../../../../../../utils/index";
import * as exchangeActions from "../../../../../../actions/cabinet/exchange";
import * as actions from "../../../../../../actions/";
// import { API_ENTRY } from "../../../../../../services/api";
import getTimezone from "./timezones";
import langCodes from "./langCodes";
import LoadingStatus from "../../../../../components/cabinet/LoadingStatus/LoadingStatus";
import { getCssVar } from "src/utils/index";
import * as utils from "../../../../../../utils";

let endpoint;
if (utils.isProduction()) {
  endpoint = "https://ex.narfex.dev";
} else {
  endpoint =
    process.env.REACT_APP_LOCAL_EXCHANGE_ENDPOINT ??
    "https://api-stage.narfex.dev";
}

class Chart extends React.PureComponent {
  static defaultProps = {
    symbol: "ETH:USDT",
    interval: "1",
    resolution: "1",
    containerId: "tv_chart_container",
    // datafeedUrl: API_ENTRY + "/api/v1/exchange_chart",
    datafeedUrl: `${endpoint}/chart`,
    // datafeedUrl: 'http://demo_feed.tradingview.com',
    libraryPath: "charting_library/",
    chartsStorageUrl: "https://saveload.tradingview.com",
    chartsStorageApiVersion: "1.1",
    clientId: "tradingview.com",
    userId: "public_user_id",
    fullscreen: false,
    autosize: true,
    studiesOverrides: {}
  };

  state = {
    status: "loading"
  };

  tvWidget = null;

  __handleFullscreen() {
    if (!document.webkitIsFullScreen && !document.fullscreen) {
      exchangeActions.setFullscreen(false);
    }
  }

  componentDidMount() {
    document.addEventListener(
      "fullscreenchange",
      this.__handleFullscreen.bind(this),
      false
    );
    document.addEventListener(
      "webkitfullscreenchange",
      this.__handleFullscreen.bind(this),
      false
    );

    const lang = actions.getCurrentLang();
    const locale = langCodes[lang.value] || lang.value;

    const widgetOptions = {
      symbol: this.props.symbol,
      // symbol: 'AA',
      // BEWARE: no trailing slash is expected in feed URL
      datafeed: new window.Datafeeds.UDFCompatibleDatafeed(
        this.props.datafeedUrl
      ),
      interval: this.props.interval,
      container_id: this.props.containerId,
      library_path: this.props.libraryPath,
      theme: ucfirst(this.props.theme),
      locale: locale,
      disabled_features: [
        "header_widget",
        "edit_buttons_in_legend",
        // 'context_menus',
        "use_localstorage_for_settings",
        "go_to_date",
        "timeframes_toolbar",
        "shift_visible_range_on_new_bar",
        "compare_symbol",
        "header_symbol_search",
        "symbol_search_hot_key",
        "header_settings",
        "header_fullscreen_button",
        "header_compare",
        "header_screenshot",
        "header_saveload",
        "symbol_info",
        "header_resolutions",
        "border_around_the_chart"
      ],
      enabled_features: [
        "charting_library_debug_mode",
        "side_toolbar_in_fullscreen_mode",
        "move_logo_to_main_pane"
        // 'hide_left_toolbar_by_default'
      ],

      charts_storage_url: this.props.chartsStorageUrl,
      charts_storage_api_version: this.props.chartsStorageApiVersion,
      client_id: this.props.clientId,
      user_id: this.props.userId,
      fullscreen: this.props.fullscreen,
      autosize: this.props.autosize,
      studies_overrides: {
        "volume.volume.color.0": "#eb6456",
        "volume.volume.color.1": "#68c2ab",
        ...this.props.studiesOverrides
      },
      overrides: {
        "paneProperties.background": getCssVar("--primary-background"),
        "paneProperties.crossHairProperties.color": getCssVar("--light-gray"),
        "scalesProperties.lineColor": getCssVar("--light-gray"),
        "scalesProperties.textColor": getCssVar("--text-black"),
        "mainSeriesProperties.candleStyle.drawBorder": false,
        "mainSeriesProperties.candleStyle.wickUpColor": getCssVar("--green"),
        "mainSeriesProperties.candleStyle.wickDownColor": getCssVar("--red"),
        "paneProperties.horzGridProperties.color": getCssVar("--light-gray"),
        "paneProperties.vertGridProperties.color": getCssVar("--light-gray"),
        "mainSeriesProperties.candleStyle.upColor": getCssVar("--green"),
        "mainSeriesProperties.candleStyle.downColor": getCssVar("--red")
      },
      allow_symbol_change: false,
      timezone: getTimezone(),
      time_frames: []
    };

    const tvWidget = new widget(widgetOptions);
    this.tvWidget = tvWidget;

    tvWidget.onChartReady(() => {
      this.activeChart = this.tvWidget.activeChart();
      this.setState({ status: "" });
    });
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (prevProps.interval !== this.props.interval && this.activeChart) {
      this.activeChart.setResolution(this.props.interval.toString());
    }

    if (prevProps.theme !== this.props.theme) {
      if (this.tvWidget && !this.state.status) {
        this.tvWidget.changeTheme(ucfirst(this.props.theme));
        this.tvWidget.applyOverrides({
          "paneProperties.background": getCssVar("--primary-background")
        });
        this.tvWidget.applyOverrides({
          "paneProperties.background": getCssVar("--primary-background")
        });
        this.tvWidget.applyOverrides({
          "paneProperties.crossHairProperties.color": getCssVar("--light-gray")
        });
        this.tvWidget.applyOverrides({
          "scalesProperties.lineColor": getCssVar("--light-gray")
        });
        this.tvWidget.applyOverrides({
          "scalesProperties.textColor": getCssVar("--text-black")
        });
        this.tvWidget.applyOverrides({
          "mainSeriesProperties.candleStyle.drawBorder": false
        });
        this.tvWidget.applyOverrides({
          "mainSeriesProperties.candleStyle.wickUpColor": getCssVar("--green")
        });
        this.tvWidget.applyOverrides({
          "mainSeriesProperties.candleStyle.wickDownColor": getCssVar("--red")
        });
        this.tvWidget.applyOverrides({
          "paneProperties.horzGridProperties.color": getCssVar("--light-gray")
        });
        this.tvWidget.applyOverrides({
          "paneProperties.vertGridProperties.color": getCssVar("--light-gray")
        });
        this.tvWidget.applyOverrides({
          "mainSeriesProperties.candleStyle.upColor": getCssVar("--green")
        });
        this.tvWidget.applyOverrides({
          "mainSeriesProperties.candleStyle.downColor": getCssVar("--red")
        });
        // TODO: HACK необходимо обновиться до версии tw 1.16 где есть поддержка тем через кастомные сваойства
      }
    }

    if (prevProps.fullscreen !== this.props.fullscreen) {
      if (this.props.fullscreen) {
        const { tradingView } = this.refs;
        if (tradingView.requestFullscreen) {
          tradingView.requestFullscreen();
        } else {
          tradingView.webkitRequestFullScreen();
        }
      }
    }

    if (prevProps.loadingStatus === "reloading" && !this.props.loadingStatus) {
      // TODO reload Chart
    }
    return false;
  }

  componentWillUnmount() {
    // clearInterval(this.chartSetInterval);
    if (this.tvWidget !== null) {
      this.tvWidget.remove();
      this.tvWidget = null;
    }
  }

  render() {
    return (
      <div className={cn("ExchangeChart", this.state.status)}>
        {this.state.status && <LoadingStatus status={this.state.status} />}
        <div
          id={this.props.containerId}
          ref="tradingView"
          className={cn("ExchangeChart__tradingView", {
            fullscreen: this.props.fullscreen
          })}
        />
      </div>
    );
  }
}

export default connect(state => ({
  theme: state.default.theme,
  loadingStatus: state.exchange.loadingStatus.default
}))(Chart);
