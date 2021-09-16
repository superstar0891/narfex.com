import "./CabinetTokenScreen.less";

import React, { useEffect } from "react";
import PageContainer from "../../../components/cabinet/PageContainer/PageContainer";
import { Helmet } from "react-helmet";
import COMPANY from "src/index/constants/company";
import { getLang } from "../../../../utils";

import Form from "./components/Form/Form";
import Stages from "./components/Stages/Stages";
import { useAdaptive } from "../../../../hooks";
import { ContentBox } from "../../../../ui";
import { tokenInit } from "../../../../actions/cabinet/token";
import { useDispatch } from "react-redux";
import { setTitle } from "../../../../actions";

export default () => {
  const adaptive = useAdaptive();
  const dispatch = useDispatch();

  useEffect(() => {
    setTitle(getLang("cabinet_token_title", true));
    dispatch(tokenInit());
  }, [dispatch]);

  return (
    <PageContainer
      className="CabinetTokenScreen"
      invert
      sideBar={
        adaptive ? (
          <ContentBox className="CabinetTokenScreen__stagesWrapper">
            <Stages />
          </ContentBox>
        ) : (
          <Stages />
        )
      }
    >
      <Helmet>
        <title>
          {[COMPANY.name, getLang("cabinet_token_title", true)].join(" - ")}
        </title>
      </Helmet>
      <Form />
    </PageContainer>
  );
};
