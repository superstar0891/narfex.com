import "./CabinetMerchantStatusScreen.less";

import React from "react";

import { getLang } from "../../../../utils";
import * as UI from "../../../../ui";
import router from "../../../../router";
import * as pages from "src/index/constants/pages";
import { Helmet } from "react-helmet";
import COMPANY from "../../../constants/company";

export function Status(props) {
  const success = props.status === "success";
  const title = success
    ? getLang("cabinet_merchantSuccessText")
    : getLang("cabinet_merchantErrorText");

  return (
    <div className="MerchantStatus">
      <Helmet>
        <title>{[COMPANY.name, title].join(" - ")}</title>
      </Helmet>
      <div
        className="MerchantStatus__icon"
        style={{
          backgroundImage: `url(${
            success
              ? require("../../../../asset/120/success.svg")
              : require("../../../../asset/120/error.svg")
          })`
        }}
      />
      <h2 className="MerchantStatus__text">{title}</h2>
      <UI.Button
        onClick={() => {
          if (props.onClose) {
            props.onClose();
          } else {
            router.navigate(pages.WALLET, { section: "fiat" });
          }
        }}
      >
        {getLang("global_continue")}
      </UI.Button>
    </div>
  );
}

export default props => {
  const { status } = router.getState().params;
  return (
    <div className="MerchantScreen">
      <Status status={status.toLowerCase()} />
    </div>
  );
};
