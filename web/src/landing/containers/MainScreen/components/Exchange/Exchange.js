import React from "react";
import { useRouter } from "react-router5";
import "./Exchange.less";
import { useSelector } from "react-redux";
import { landingSelector } from "../../../../../selectors";
import { getCurrencyInfo } from "../../../../../actions";
import { Button, CircleIcon, NumberFormat } from "../../../../../ui";
import Chart from "../Chart/Chart";
import { joinComponents } from "../../../../../utils";
import * as actions from "../../../../../actions";
import * as pages from "../../../../../index/constants/pages";
import Lang from "../../../../../components/Lang/Lang";
import Skeleton from "../../../../../ui/components/Skeleton/Skeleton";

export default () => {
  const { markets } = useSelector(landingSelector);
  const router = useRouter();

  return (
    <div className="Exchange LandingWrapper__block">
      <div className="LandingWrapper__content Exchange__content">
        <h2>
          <Lang name="landing_exchange_title" />
        </h2>
        <p>
          <Lang name="landing_exchange_description" />
        </p>
        <table>
          <tr>
            <th>
              <Lang name="landing_exchange_table_name" />
            </th>
            <th>
              <span className="Exchange__desktopOnly">
                <Lang name="lending_exchange_table_lastPrice" />
              </span>
              <span className="Exchange__mobileOnly">
                <Lang name="lending_exchange_table_lastPrice_mobile" />
              </span>
            </th>
            <th>
              <span className="Exchange__desktopOnly">
                <Lang name="landing_exchange_table_24h" />
              </span>
              <span className="Exchange__mobileOnly">
                <Lang name="landing_exchange_table_24h_mobile" />
              </span>
            </th>
            <th className="Exchange__chartColumn">
              <Lang name="landing_exchange_table_market" />
            </th>
          </tr>
          {markets.length
            ? markets
                .filter(
                  m =>
                    m.market.config.secondary_coin.name === "usdt" &&
                    m.market.config.primary_coin.name !== "nrfx" // TODO: NRFX HACK
                )
                .map(({ market: { config }, chart, ticker }, key) => {
                  const currency = getCurrencyInfo(config.primary_coin.name);

                  return (
                    <tr
                      key={key}
                      onClick={() => {
                        router.navigate(pages.EXCHANGE, {
                          market: ticker.market?.toLowerCase().replace("/", "_")
                        });
                      }}
                    >
                      <td>
                        <div className="Exchange__currency">
                          <CircleIcon currency={currency} />
                          <div className="Exchange__currency__name">
                            <strong>{currency.abbr.toUpperCase()}</strong>
                            <span>{currency.name}</span>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div className="Exchange__price">
                          {ticker.usd_price
                            .toFixed(2)
                            .toString()
                            .split(".")
                            .map((n, i) => (
                              <span>
                                {i === 1 ? "." : "$"}
                                {n}
                              </span>
                            ))
                            .reduce(joinComponents(""), null)}
                        </div>
                      </td>
                      <td>
                        <NumberFormat
                          symbol
                          color={ticker.percent !== 0}
                          percent
                          indicator
                          number={ticker.percent}
                        />
                      </td>
                      <td className="Exchange__chartColumn">
                        <Chart currency={currency} chart={chart} />
                      </td>
                    </tr>
                  );
                })
            : Array(5)
                .fill(true)
                .map((i, key) => (
                  <tr key={`s${key}`}>
                    <td>
                      <div className="Exchange__currency">
                        <CircleIcon skeleton />
                        <Skeleton />
                      </div>
                    </td>
                    <td>
                      <Skeleton />
                    </td>
                    <td>
                      <Skeleton />
                    </td>
                    <td className="Exchange__chartColumn">
                      <Skeleton className="Chart" />
                    </td>
                  </tr>
                ))}
        </table>

        <Button
          onClick={() => {
            actions.openPage(pages.EXCHANGE);
          }}
          type="secondary"
          size="extra_large"
        >
          <Lang name="landing_exchange_viewMoreMarkets" /> ›
        </Button>
      </div>
    </div>
  );
};
