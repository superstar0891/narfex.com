import "./MethodList.less";
import React from "react";
import { connect } from "react-redux";
import { ContentBox, Editor, Label } from "../../../../ui";
import router from "../../../../router";
import * as PAGES from "../../../constants/pages";
import { sortDocSchema } from "../utils";
import { joinComponents } from "src/utils";
import LoadingStatus from "../../../components/cabinet/LoadingStatus/LoadingStatus";
import { Helmet } from "react-helmet";
import COMPANY from "../../../constants/company";
import { API_VERSION } from "../../../../services/api";

const MethodList = props => {
  const handleMethodClick = (method, name) => () => {
    if (method.key) {
      router.navigate(PAGES.DOCUMENTATION_API_METHOD, {
        key: method.key
      });
    } else {
      router.navigate(PAGES.DOCUMENTATION_API_LIST, {
        path: [...(path ? path.split("-") : []), name].join("-")
      });
    }
  };

  const render = (name, schema) => {
    let pathLink = "";
    const pathList = props.routerParams.path
      ? props.routerParams.path.split("-").map(p => {
          pathLink = [pathLink, p].filter(Boolean).join("-");
          return {
            label: p.toLowerCase(),
            path: pathLink
          };
        })
      : null;
    return (
      <div className="Documentation_wrapper__content MethodList">
        <Helmet>
          <title>{[COMPANY.name, name].join(" - ")}</title>
        </Helmet>
        <ContentBox>
          <h1 className="MethodList__title">{name}</h1>
          <h2 className="MethodList__path">
            /
            <span
              onClick={() => router.navigate(PAGES.DOCUMENTATION_API)}
              className="link"
            >
              api
            </span>
            {`/v${API_VERSION}/`}
            {pathList &&
              pathList
                .map(p => (
                  <span
                    onClick={() =>
                      router.navigate(PAGES.DOCUMENTATION_API_LIST, {
                        path: p.path
                      })
                    }
                    className="link"
                  >
                    {p.label}
                  </span>
                ))
                .reduce(joinComponents("/"), null)}
          </h2>
          <div className="MethodList__list">
            {Object.keys(schema)
              .filter(i => i !== "opened")
              .sort(sortDocSchema(schema))
              .map(name => {
                const method = schema[name];
                return (
                  <div
                    key={name}
                    onClick={handleMethodClick(method, name)}
                    className="MethodList__list__line"
                  >
                    {pathList && (
                      <div className="MethodList__list__method">
                        {schema[name].method && (
                          <Label
                            type={
                              schema[name].method === "DELETE"
                                ? "DEL"
                                : schema[name].method
                            }
                          />
                        )}
                      </div>
                    )}
                    <div className="MethodList__list__methodName">
                      <strong>
                        {schema[name].name === "Default"
                          ? "/"
                          : schema[name].name || name}
                      </strong>
                    </div>
                    <div className="MethodList__list__description">
                      <Editor short readOnly content={method.description} />
                    </div>
                  </div>
                );
              })}
          </div>
        </ContentBox>
      </div>
    );
  };

  const schemaPath = (name, schema, path) => {
    if (!schema) {
      return <LoadingStatus status={"not_found"} />;
    }

    if (!path.length) {
      if (!schema.key) {
        return render(name, schema);
      }
    }

    return schemaPath(path[0], schema[path[0]], path.slice(1));
  };

  const { path } = props.routerParams;

  return schemaPath("API", props.schema, path ? path.split("-") : []);
};

export default connect(state => ({
  schema: state.documentation.schema
}))(MethodList);
