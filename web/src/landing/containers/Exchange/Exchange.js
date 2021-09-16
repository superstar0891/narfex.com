import React from "react";
import Welcome from "../MainScreen/components/Welcome/Welcome";
import Promo from "../MainScreen/components/Promo/Promo";
import Advantages from "../MainScreen/components/Advantages/Advantages";
import Application from "../MainScreen/components/Application/Application";
import Exchange from "../MainScreen/components/Exchange/Exchange";
import Lang from "../../../components/Lang/Lang";
import * as pages from "../../../index/constants/pages";
import COMPANY from "../../../index/constants/company";
import * as utils from "../../../utils";
import { Helmet } from "react-helmet";

export default () => {
  return (
    <div>
      <Helmet>
        <title>
          {[
            COMPANY.name,
            utils.getLang("landingExchange_promo_title", true)
          ].join(" - ")}
        </title>
        <meta
          name="description"
          content={utils.getLang("landingExchange_promo_description")}
        />
      </Helmet>
      <Promo
        title={<Lang name="landingExchange_promo_title" />}
        description={<Lang name="landingExchange_promo_description" />}
        actionButtonText={<Lang name="landingExchange_promo_actionButton" />}
        image={require("../MainScreen/components/Promo/assets/exchange.jpg")}
      />
      <Advantages
        accent
        mode={"center"}
        items={[
          {
            icon: require("../../../asset/120/fee.svg"),
            titleLang: "landingExchange_advantages_fee_title",
            textLang: "landingExchange_advantages_fee_description"
          },
          {
            icon: require("../../../asset/120/legality.svg"),
            titleLang: "landingExchange_advantages_legality_title",
            textLang: "landingExchange_advantages_legality_description"
          },
          {
            icon: require("../../../asset/120/reliability.svg"),
            titleLang: "landingExchange_advantages_api_title",
            textLang: "landingExchange_advantages_api_description",
            linkLang: "landingExchange_advantages_api_link",
            routeName: pages.DOCUMENTATION_API
          }
        ]}
      />
      <Exchange />
      <Application accent />
      <Advantages
        titleLang="landing_advantages_title"
        items={[
          {
            icon: require("../../../asset/120/fast.svg"),
            titleLang: "landing_advantages_performance_title",
            textLang: "landing_advantages_performance_description"
          },
          {
            icon: require("../../../asset/120/wallet.svg"),
            titleLang: "landing_advantages_multiCurrency_title",
            textLang: "landing_advantages_multiCurrency_description"
          },
          {
            icon: require("../../../asset/120/trade.svg"),
            titleLang: "landing_advantages_liquidity_title",
            textLang: "landing_advantages_liquidity_description"
          },
          {
            icon: require("../../../asset/120/fee.svg"),
            titleLang: "landing_advantages_fee_title",
            textLang: "landing_advantages_fee_description"
          },
          {
            icon: require("../../../asset/120/product.svg"),
            titleLang: "landing_advantages_ui_title",
            textLang: "landing_advantages_ui_description"
          },
          {
            icon: require("../../../asset/120/support.svg"),
            titleLang: "landing_advantages_support_title",
            textLang: "landing_advantages_support_description"
          }
        ]}
      />
      <Welcome />
    </div>
  );
};
