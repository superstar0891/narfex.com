import "./SiteFeeScreen.less";

import React from "react";
import NumberFormat from "src/ui/components/NumberFormat/NumberFormat";

import * as UI from "src/ui";
import { getLang } from "src/utils";

import CurrencyLabel from "./components/CurrencyLable/CurrencyLabel";
import { Helmet } from "react-helmet";
import * as utils from "src/utils";
import COMPANY from "src/index/constants/company";
import Welcome from "../MainScreen/components/Welcome/Welcome";
import { useAdaptive } from "src/hooks";

export default () => {
  const adaptive = useAdaptive();
  const renderContent = () => {
    return (
      <>
        <h2>{getLang("site__fee_depositAndWithdraw")}</h2>
        <UI.ContentBox className="SiteFeeScreen__table">
          <table>
            <tr>
              <th>{getLang("site__fee_cryptocurrencies")}</th>
              <th>
                <CurrencyLabel abbr="btc" />
              </th>
              <th>
                <CurrencyLabel abbr="eth" />
              </th>
              <th>
                <CurrencyLabel abbr="ltc" />
              </th>
            </tr>
            <tr>
              <td>{getLang("site__fee_minimumInput")}</td>
              <td colSpan="3">{getLang("site__fee_noLimits")}</td>
            </tr>
            <tr>
              <td>{getLang("site__fee_maximumInput")}</td>
              <td colSpan="3">{getLang("site__fee_noLimits")}</td>
            </tr>
            <tr>
              <td>{getLang("site__fee_сommissionForInput")}</td>
              <td colSpan="3">{getLang("site__fee_noFee")}</td>
            </tr>
            <tr>
              <td>{getLang("site__fee_minimumWithdrawal")}</td>
              <td>
                <NumberFormat number={0.001} currency="btc" />
              </td>
              <td>
                <NumberFormat number={0.02} currency="eth" />
              </td>
              <td>
                <NumberFormat number={0.002} currency="ltc" />
              </td>
            </tr>
            <tr>
              <td>{getLang("site__fee_withdrawalFee")}</td>
              <td>
                <NumberFormat number={0.0004} currency="btc" />
              </td>
              <td>
                <NumberFormat number={0.01} currency="eth" />
              </td>
              <td>
                <NumberFormat number={0.001} currency="ltc" />
              </td>
            </tr>
          </table>
        </UI.ContentBox>
        {/*<UI.ContentBox className="SiteFeeScreen__table">*/}
        {/*  <table>*/}
        {/*    <tr>*/}
        {/*      <th>{getLang("site__fee_fiatCurrency")}</th>*/}
        {/*      <th>*/}
        {/*        <CurrencyLabel abbr="usd" />*/}
        {/*      </th>*/}
        {/*      <th>*/}
        {/*        <CurrencyLabel abbr="eur" />*/}
        {/*      </th>*/}
        {/*      <th>*/}
        {/*        <CurrencyLabel abbr="rub" />*/}
        {/*      </th>*/}
        {/*      <th>*/}
        {/*        <CurrencyLabel abbr="idr" />*/}
        {/*      </th>*/}
        {/*    </tr>*/}
        {/*    <tr>*/}
        {/*      <td>{getLang("site__fee_minimumInput")}</td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={100} currency="usd" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={100} currency="eur" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={6000} currency="rub" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={350000} currency="idr" />*/}
        {/*      </td>*/}
        {/*    </tr>*/}
        {/*    <tr>*/}
        {/*      <td>{getLang("site__fee_maximumInput")}</td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={50000} currency="usd" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={50000} currency="eur" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={2000000} currency="rub" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={200000000} currency="idr" />*/}
        {/*      </td>*/}
        {/*    </tr>*/}
        {/*    <tr>*/}
        {/*      <td>{getLang("site__fee_сommissionForInput")}</td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={60} currency="usd" /> или{" "}*/}
        {/*        <NumberFormat number={1} percent />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={60} currency="eur" /> или{" "}*/}
        {/*        <NumberFormat number={1} percent />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={4000} currency="rub" /> или{" "}*/}
        {/*        <NumberFormat number={1} percent />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={10000} currency="idr" /> или{" "}*/}
        {/*        <NumberFormat number={1} percent />*/}
        {/*      </td>*/}
        {/*    </tr>*/}
        {/*    <tr>*/}
        {/*      <td>{getLang("site__fee_minimumWithdrawal")}</td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={100} currency="usd" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={100} currency="eur" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={6000} currency="rub" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={100000} currency="idr" />*/}
        {/*      </td>*/}
        {/*    </tr>*/}
        {/*    <tr>*/}
        {/*      <td>{getLang("site__fee_maximumWithdrawal")}</td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={25000} currency="usd" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={25000} currency="eur" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={1000000} currency="rub" />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={100000000} currency="idr" />*/}
        {/*      </td>*/}
        {/*    </tr>*/}
        {/*    <tr>*/}
        {/*      <td>{getLang("site__fee_withdrawalFee")}</td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={60} currency="usd" /> или{" "}*/}
        {/*        <NumberFormat number={1} percent />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={60} currency="eur" /> или{" "}*/}
        {/*        <NumberFormat number={1} percent />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={4000} currency="rub" /> или{" "}*/}
        {/*        <NumberFormat number={1} percent />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={10000} currency="idr" /> или{" "}*/}
        {/*        <NumberFormat number={1} percent />*/}
        {/*      </td>*/}
        {/*    </tr>*/}
        {/*  </table>*/}
        {/*</UI.ContentBox>*/}
        {/*<h2>{getLang("site__fee_platformFeeTitle")}</h2>*/}
        {/*<UI.ContentBox className="SiteFeeScreen__table">*/}
        {/*  <table>*/}
        {/*    <tr>*/}
        {/*      <th>{getLang("site__fee_feeType")}</th>*/}
        {/*      <th>{getLang("site__fee_percent")}</th>*/}
        {/*      <th>{getLang("site__fee_minimumBidding")}</th>*/}
        {/*      <th>{getLang("site__fee_maximumBidding")}</th>*/}
        {/*    </tr>*/}
        {/*    <tr>*/}
        {/*      <td>{getLang("site__fee_exchangeFee")}</td>*/}
        {/*      <td>*/}
        {/*        <NumberFormat number={0.01} percent />*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <p>{getLang("site__fee_minimumBiddingFee")}</p>*/}
        {/*      </td>*/}
        {/*      <td>*/}
        {/*        <p>{getLang("site__fee_maximumBiddingFee")}</p>*/}
        {/*      </td>*/}
        {/*    </tr>*/}
        {/*  </table>*/}
        {/*</UI.ContentBox>*/}
      </>
    );
  };

  const renderContentAdaptive = () => {
    return (
      <>
        <div className="SiteFeeScreen__list">
          <div className="SiteFeeScreen__list__title">
            <CurrencyLabel abbr="btc" />
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_minimumInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noLimits")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_maximumInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noLimits")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_сommissionForInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noFee")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_minimumWithdrawal")}
            </div>
            <div className="SiteFeeScreen__list__value">
              <NumberFormat number={0.01} currency="btc" />
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_withdrawalFee")}
            </div>
            <div className="SiteFeeScreen__list__value">
              <NumberFormat number={0.0004} currency="btc" />
            </div>
          </div>
        </div>
        <div className="SiteFeeScreen__list">
          <div className="SiteFeeScreen__list__title">
            <CurrencyLabel abbr="eth" />
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_minimumInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noLimits")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_maximumInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noLimits")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_сommissionForInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noFee")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_minimumWithdrawal")}
            </div>
            <div className="SiteFeeScreen__list__value">
              <NumberFormat number={0.02} currency="eth" />
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_withdrawalFee")}
            </div>
            <div className="SiteFeeScreen__list__value">
              <NumberFormat number={0.01} currency="eth" />
            </div>
          </div>
        </div>
        <div className="SiteFeeScreen__list">
          <div className="SiteFeeScreen__list__title">
            <CurrencyLabel abbr="ltc" />
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_minimumInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noLimits")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_maximumInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noLimits")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_сommissionForInput")}
            </div>
            <div className="SiteFeeScreen__list__value">
              {getLang("site__fee_noFee")}
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_minimumWithdrawal")}
            </div>
            <div className="SiteFeeScreen__list__value">
              <NumberFormat number={0.002} currency="ltc" />
            </div>
          </div>
          <div className="SiteFeeScreen__list__row">
            <div className="SiteFeeScreen__list__label">
              {getLang("site__fee_withdrawalFee")}
            </div>
            <div className="SiteFeeScreen__list__value">
              <NumberFormat number={0.001} currency="ltc" />
            </div>
          </div>
        </div>
        {/*<div className="SiteFeeScreen__list">*/}
        {/*  <div className="SiteFeeScreen__list__title">*/}
        {/*    <CurrencyLabel abbr="usd" />*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={100} currency="usd" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={50000} currency="usd" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_сommissionForInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={60} currency="usd" /> или{" "}*/}
        {/*      <NumberFormat number={1} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumWithdrawal")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={100} currency="usd" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumWithdrawal")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={25000} currency="usd" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_withdrawalFee")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={60} currency="usd" /> или{" "}*/}
        {/*      <NumberFormat number={1} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*</div>*/}
        {/*<div className="SiteFeeScreen__list">*/}
        {/*  <div className="SiteFeeScreen__list__title">*/}
        {/*    <CurrencyLabel abbr="eur" />*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={100} currency="eur" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={50000} currency="eur" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_сommissionForInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={60} currency="eur" /> или{" "}*/}
        {/*      <NumberFormat number={1} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumWithdrawal")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={100} currency="eur" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumWithdrawal")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={25000} currency="eur" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_withdrawalFee")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={60} currency="eur" /> или{" "}*/}
        {/*      <NumberFormat number={1} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*</div>*/}
        {/*<div className="SiteFeeScreen__list">*/}
        {/*  <div className="SiteFeeScreen__list__title">*/}
        {/*    <CurrencyLabel abbr="rub" />*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={6000} currency="rub" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={2000000} currency="rub" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_сommissionForInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={4000} currency="rub" /> или{" "}*/}
        {/*      <NumberFormat number={1} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumWithdrawal")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={6000} currency="rub" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumWithdrawal")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={1000000} currency="rub" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_withdrawalFee")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={4000} currency="rub" /> или{" "}*/}
        {/*      <NumberFormat number={1} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*</div>*/}
        {/*<div className="SiteFeeScreen__list">*/}
        {/*  <div className="SiteFeeScreen__list__title">*/}
        {/*    <CurrencyLabel abbr="idr" />*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={350000} currency="idr" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={200000000} currency="idr" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_сommissionForInput")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={6000} currency="idr" /> или{" "}*/}
        {/*      <NumberFormat number={1} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumWithdrawal")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={100000} currency="idr" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumWithdrawal")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={100000000} currency="idr" />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__row">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_withdrawalFee")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={6000} currency="idr" /> или{" "}*/}
        {/*      <NumberFormat number={1} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*</div>*/}
        {/*<div className="SiteFeeScreen__list">*/}
        {/*  <div className="SiteFeeScreen__list__title">*/}
        {/*    {getLang("site__fee_exchangeFee")}*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__item">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_percent")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      <NumberFormat number={0.01} percent />*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__item">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_minimumBidding")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      {getLang("site__fee_minimumBiddingFee")}*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*  <div className="SiteFeeScreen__list__item">*/}
        {/*    <div className="SiteFeeScreen__list__label">*/}
        {/*      {getLang("site__fee_maximumBidding")}*/}
        {/*    </div>*/}
        {/*    <div className="SiteFeeScreen__list__value">*/}
        {/*      {getLang("site__fee_maximumBiddingFee")}*/}
        {/*    </div>*/}
        {/*  </div>*/}
        {/*</div>*/}
      </>
    );
  };

  return (
    <>
      <div className="SiteFeeScreen LandingWrapper__block">
        <div className="LandingWrapper__content">
          <Helmet>
            <title>
              {[COMPANY.name, utils.getLang("site__fee_title", true)].join(
                " - "
              )}
            </title>
            <meta
              name="description"
              content={utils.getLang("site__fee_description")}
            />
          </Helmet>
          <div className="SiteContactScreen__heading">
            <h1>{getLang("site__fee_title")}</h1>
            <p>
              {getLang("site__fee_description")}
              <br />
              {getLang("site__fee_description2")}
            </p>
          </div>
          {adaptive ? renderContentAdaptive() : renderContent()}
        </div>
      </div>
      <Welcome />
    </>
  );
};
