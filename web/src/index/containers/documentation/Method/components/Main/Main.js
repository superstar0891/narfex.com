import "./Main.less";

import React from "react";
import { connect } from "react-redux";
import { updateMethod, saveMethod } from "src/actions/documentation";
import { ContentBox, Label, Button, Editor } from "src/ui";
import router from "../../../../../../router";
import { getLang, ucfirst } from "src/utils";
import * as PAGES from "../../../../../constants/pages";
import COMPANY from "../../../../../constants/company";
import { Helmet } from "react-helmet";
import { API_VERSION } from "src/services/api";

const Main = props => {
  const handleChange = value => props.updateMethod("description", value);

  const title = props.name === "Default" ? ucfirst(props.path) : props.name;

  let pathLink = "";
  const pathList = props.path.split("/").map(p => {
    pathLink = [pathLink, ucfirst(p)].filter(Boolean).join("-");
    return {
      label: p,
      path: pathLink
    };
  });

  return (
    <ContentBox className="Method__main">
      <Helmet>
        <title>{[COMPANY.name, title].join(" - ")}</title>
      </Helmet>
      <h1 className="Method__main__title">
        <span>{title}</span>
        {props.editMode && (
          <Button
            state={props.saveStatus}
            onClick={props.saveMethod}
            size="small"
          >
            {getLang("global_save")}
          </Button>
        )}
      </h1>
      <div className="Method__main__path">
        <h3>
          /
          <span
            className="link"
            onClick={() => {
              router.navigate(PAGES.DOCUMENTATION_API);
            }}
          >
            api
          </span>
          {`/v${API_VERSION}/`}
          {pathList.map((p, i) => {
            return i < pathList.length - 1 ? (
              <>
                <span
                  className="link"
                  onClick={() => {
                    router.navigate(PAGES.DOCUMENTATION_API_LIST, {
                      path: p.path
                    });
                  }}
                >
                  {p.label}
                </span>
                /
              </>
            ) : (
              p.label
            );
          })}
        </h3>
        <Label type={props.method} />
      </div>
      <div className="Method__main__content">
        <Editor
          readOnly={!props.editMode}
          border={props.editMode}
          content={props.description}
          onChange={handleChange}
        />
      </div>
      {!!props.requirements.length && (
        <div className="Method__main__requirements">
          <div className="Method__main__requirements__title">
            {getLang("cabinet_docsRequirements")}:
          </div>
          {props.requirements.map(item => (
            <Label
              key={item}
              onClick={() => {
                router.navigate(PAGES.DOCUMENTATION_PAGE, {
                  page: item
                });
              }}
              title={item}
            />
          ))}
        </div>
      )}
    </ContentBox>
  );
};

export default connect(
  state => ({
    editMode: state.documentation.editMode,
    name: state.documentation.method.name,
    description: state.documentation.method.description,
    method: state.documentation.method.method,
    path: state.documentation.method.path,
    saveStatus: state.documentation.loadingStatus.saveMethod,
    requirements: state.documentation.method.requirements
  }),
  {
    updateMethod,
    saveMethod
  }
)(Main);
