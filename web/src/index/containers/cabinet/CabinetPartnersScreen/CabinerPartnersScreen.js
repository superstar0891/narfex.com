import React, { useEffect } from "react";
import { useSelector } from "react-redux";
import PageContainer from "../../../components/cabinet/PageContainer/PageContainer";
import PromoCode from "./components/PromoCode/PromoCode";
import Balances from "./components/Balances/Balances";
import Ratings from "./components/Ratings/Ratings";
import { useDispatch } from "react-redux";
import { partnersFetch } from "../../../../actions/cabinet/partners";
import History from "./components/History/History";
import LoadingStatus from "../../../components/cabinet/LoadingStatus/LoadingStatus";
import { partnersStatusSelector } from "../../../../selectors";
import { setTitle } from "../../../../actions";
import * as utils from "../../../../utils";
import COMPANY from "../../../constants/company";
import { Helmet } from "react-helmet";

export default () => {
  const dispatch = useDispatch();
  const status = useSelector(partnersStatusSelector("main"));

  useEffect(() => {
    setTitle(utils.getLang("cabinet_header_partners"));
    dispatch(partnersFetch());
  }, [dispatch]);

  if (status) {
    return <LoadingStatus status={status} />;
  }

  return (
    <PageContainer
      invert
      className="CabinetPartnersScreen"
      sideBar={
        <>
          <Balances />
          <Ratings />
        </>
      }
    >
      <Helmet>
        <title>
          {[COMPANY.name, utils.getLang("cabinet_header_partners", true)].join(
            " - "
          )}
        </title>
      </Helmet>
      <PromoCode />
      <History />
      {/*<EmptyHistory />*/}
    </PageContainer>
  );
};
