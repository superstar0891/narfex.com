import "./MainScreen.less";
import React from "react";
import company from "../../../index/constants/company";
import * as actions from "../../../actions/";
import Promo from "../../../landing/containers/MainScreen/components/Promo/Promo";

export default props => {
  return (
    <div className="MainScreen">
      <Promo
        title={company.name + " Control Panel"}
        description={"Amazing project management solution"}
        actionButtonText={"Start manage " + company.name}
        onClick={() => {
          actions.openModal("login");
        }}
        image={require("src/landing/containers/MainScreen/components/Promo/assets/company.svg")}
      />
    </div>
  );
};
