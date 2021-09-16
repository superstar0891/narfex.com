import "./Page.less";

import React, { useEffect } from "react";
import { connect } from "react-redux";
import { ContentBox, Editor, Button } from "src/ui";
import {
  getPage,
  updatePageContent,
  savePage
} from "src/actions/documentation";
import LoadingStatus from "../../../components/cabinet/LoadingStatus/LoadingStatus";
import router from "../../../../router";
import * as pages from "src/index/constants/pages";
import { Helmet } from "react-helmet";
import COMPANY from "../../../constants/company";

const Page = props => {
  useEffect(() => {
    if (props.pageName && props.page.url !== props.pageName) {
      props.getPage(props.pageName);
    } else {
      router.navigate(pages.DOCUMENTATION_PAGE, {
        page: props.welcomePageUrl
      });
    }
  }, [props.pageName]); // eslint-disable-line

  if (props.status || !props.page) {
    return (
      <div className="Documentation_wrapper__content">
        <ContentBox className="DocPage">
          <LoadingStatus status={props.status} />
        </ContentBox>
      </div>
    );
  }

  return (
    <div className="Documentation_wrapper__content">
      <Helmet>
        <title>{[COMPANY.name, props.page.title].join(" - ")}</title>
      </Helmet>
      <ContentBox className="DocPage">
        <h1 className="DocPage__title">
          {props.page.title}
          {props.editMode && (
            <Button
              state={props.savePageStatus}
              size="small"
              onClick={() => props.savePage(props.pageName)}
            >
              Save
            </Button>
          )}
        </h1>
        <Editor
          readOnly={!props.editMode}
          onChange={props.updatePageContent}
          content={props.page.content}
        />
      </ContentBox>
    </div>
  );
};

export default connect(
  state => ({
    pageName: state.router.route.params.page,
    editMode: state.documentation.editMode,
    status: state.documentation.loadingStatus.page,
    savePageStatus: state.documentation.loadingStatus.savePage,
    welcomePageUrl: state.documentation.welcomePageUrl,
    page: state.documentation.page
  }),
  {
    getPage,
    savePage,
    updatePageContent
  }
)(Page);
