import "./Method.less";

import React, { useEffect } from "react";
import { connect } from "react-redux";
import { getMethod } from "src/actions/documentation";
import Main from "./components/Main/Main";
import Form from "./components/Form/Form";
import Params from "./components/Params/Params";
import ShortDescription from "./components/ShortDescription/ShortDescription";
import Result from "./components/Result/Result";

import LoadingStatus from "../../../components/cabinet/LoadingStatus/LoadingStatus";

const DocumentationMethod = props => {
  useEffect(() => {
    props.getMethod(props.routerParams.key);
  }, [props.routerParams.key]); // eslint-disable-line

  if (props.loadingStatus || !props.loaded) {
    return <LoadingStatus status="loading" />;
  }

  return (
    <>
      <div className="Documentation_wrapper__content Method">
        <Main />
        <ShortDescription />
        <Params type="header" />
        <Params type="body" />
        <Result />
      </div>
      <div className="Documentation_wrapper__subContent">
        <Form />
      </div>
    </>
  );
};

export default connect(
  state => ({
    route: state.router.route,
    loadingStatus: state.documentation.loadingStatus.method,
    loaded: !!state.documentation.method
  }),
  {
    getMethod
  }
)(DocumentationMethod);
