import "./DocumentationWrapper.less";

import React, { useEffect } from "react";
import { connect } from "react-redux";

import { classNames as cn } from "../../utils/index";
import Header from "../../index/components/documentation/Header/Header";
import Menu from "src/index/components/documentation/Menu/Menu";
import { getDocumentation } from "src/actions/documentation";
import LoadingStatus from "../../index/components/cabinet/LoadingStatus/LoadingStatus";

const DocumentationWrapper = props => {
  useEffect(() => {
    props.getDocumentation();
  }, [props.currentLang]); // eslint-disable-line

  const renderContent = () => {
    if (props.loadingStatus) {
      return (
        <div className="Documentation_wrapper__layout">
          <LoadingStatus inline status={props.loadingStatus} />
        </div>
      );
    }

    return (
      <div className="Documentation_wrapper__layout">
        <div className="Documentation_wrapper__menu">
          <Menu />
        </div>
        {props.children}
      </div>
    );
  };

  return (
    <div className={cn("Documentation_wrapper", { pending: props.pending })}>
      <div className="Documentation_wrapper__header">
        <Header />
      </div>
      {renderContent()}
    </div>
  );
};

export default connect(
  state => ({
    loadingStatus: state.documentation.loadingStatus.default,
    currentLang: state.default.currentLang,
    schema: state.documentation.schema
  }),
  {
    getDocumentation
  }
)(DocumentationWrapper);
