import React, { useEffect } from "react";
import * as firebase from "firebase";

import Welcome from "../MainScreen/components/Welcome/Welcome";
import Promo from "../MainScreen/components/Promo/Promo";
import Advantages from "../MainScreen/components/Advantages/Advantages";
import Application from "../MainScreen/components/Application/Application";
import Steps from "../Steps/Steps";
import Lang from "../../../components/Lang/Lang";
import COMPANY from "../../../index/constants/company";
import * as utils from "../../../utils";
import { Helmet } from "react-helmet";
import ADVANTAGES_ITEMS from "../../constants/advantages";

export default () => {
  useEffect(() => {
    firebase.analytics().logEvent("open_site_buy_bitcoin_screen");
  });

  return (
    <div>
      <Helmet>
        <title>
          {[
            COMPANY.name,
            utils.getLang("landingBitcoin_promo_title", true)
          ].join(" - ")}
        </title>
        <meta
          name="description"
          content={utils.getLang("lendingBitcoin_promo_description")}
        />
      </Helmet>
      <Promo
        title={<Lang name="landingBitcoin_promo_title" />}
        description={<Lang name="lendingBitcoin_promo_description" />}
        actionButtonText={<Lang name="landingBitcoin_promo_actionButton" />}
        image={require("../MainScreen/components/Promo/assets/bitcoin.svg")}
      />
      <Steps />
      {/*<Swap />*/}
      {/*<Exchange />*/}
      <Advantages
        titleLang="landingBitcoin_advantages_title"
        items={ADVANTAGES_ITEMS}
      />
      <Application accent />
      <Advantages
        type="alternative"
        titleLang="landingBitcoin_use_title"
        items={[
          {
            icon: require("../../../asset/120/keep.svg"),
            titleLang: "landingBitcoin_use_keepId_title",
            textLang: "landingBitcoin_use_keepId_description"
          },
          {
            icon: require("../../../asset/120/trade.svg"),
            titleLang: "landingBitcoin_use_SellIt_title",
            textLang: "landingBitcoin_use_SellIt_description"
          },
          {
            icon: require("../../../asset/120/exchange.svg"),
            titleLang: "landingBitcoin_use_exchangeIt_title",
            textLang: "landingBitcoin_use_exchangeIt_description"
          },
          {
            icon: require("../../../asset/120/pay.svg"),
            titleLang: "landingBitcoin_use_payWithIt_title",
            textLang: "landingBitcoin_use_payWithIt_description"
          },
          {
            icon: require("../../../asset/120/send.svg"),
            titleLang: "landingBitcoin_use_SendIt_title",
            textLang: "landingBitcoin_use_SendIt_description"
          },
          {
            icon: require("../../../asset/120/donate.svg"),
            titleLang: "landingBitcoin_use_donateIt_title",
            textLang: "landingBitcoin_use_donateIt_description"
          }
        ]}
      />
      <Welcome
        titleLang="landingBitcoin_callToAction_title"
        actionButtonLang="landingBitcoin_callToAction_button"
      />
    </div>
  );
};
