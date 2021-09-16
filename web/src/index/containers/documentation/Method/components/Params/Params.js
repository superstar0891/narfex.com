import "./Params.less";

import React from "react";
import { connect } from "react-redux";
import { updateMethodParam } from "src/actions/documentation";
import { ContentBox, Editor } from "src/ui";
import { getLang, joinComponents } from "src/utils";

const Params = props => {
  const handleChangeParam = paramName => description => {
    props.updateMethodParam(paramName, description);
  };

  const params = props.params.filter(p => p.type === props.type);

  const titles = {
    body: getLang("cabinet_docsParameters"),
    header: getLang("cabinet_docsHttpHeaders")
  };

  return (
    !!params.length && (
      <ContentBox className="Method__params">
        <h2>{titles[props.type]}</h2>
        <div className="Method__params__list">
          {params.map(param => {
            const { filters } = param;
            return (
              <div className="Method__params__list__line">
                <div className="Method__params__param">
                  <strong>{param.name}</strong>
                  {filters.required && (
                    <small>{getLang("docs_paramRequired")}</small>
                  )}
                </div>
                <div className="Method__params__description">
                  <p>
                    {[
                      filters.required && getLang("docs_paramRequired"),
                      filters.lowercase &&
                        getLang("docs_paramLowercaseDescription"),
                      filters.uppercase &&
                        getLang("docs_paramUppercaseDescription"),
                      filters.positive &&
                        getLang("docs_paramPositiveDescription"),
                      filters.json && getLang("docs_paramJsonDescription"),
                      filters.int && getLang("docs_paramIntDescription"),
                      filters.double && getLang("docs_paramDoubleDescription"),
                      filters.email && getLang("docs_paramEmailDescription"),
                      filters.username &&
                        getLang("docs_paramUsernameDescription"),
                      filters.default && (
                        <>
                          {getLang("docs_paramDefaultDescription")}:{" "}
                          <code>{filters.default}</code>
                        </>
                      ),
                      filters.minLen && !filters.maxLen && (
                        <>
                          {getLang("docs_paramMinLenDescription")}{" "}
                          {filters.minLen} {getLang("docs_paramCharacters")}
                        </>
                      ),
                      filters.maxLen && !filters.minLen && (
                        <>
                          {getLang("docs_paramMaxLenDescription")}{" "}
                          {filters.maxLen} {getLang("docs_paramCharacters")}
                        </>
                      ),
                      filters.maxLen && filters.minLen && (
                        <>
                          {getLang("docs_paramMinLenMaxLenDescription")}{" "}
                          {filters.minLen === filters.maxLen
                            ? filters.minLen
                            : [filters.minLen, filters.maxLen].join(" - ")}{" "}
                          {getLang("docs_paramCharacters")}
                        </>
                      ),
                      filters.oneOf && (
                        <>
                          {getLang("docs_paramOneOfDescription")}
                          {": "}
                          {filters.oneOf
                            .map(item => <code>{item}</code>)
                            .reduce(joinComponents(), null)}
                        </>
                      )
                    ]
                      .filter(Boolean)
                      .reduce(joinComponents(), null)}
                  </p>
                  <p>
                    <Editor
                      readOnly={!props.editMode}
                      border={props.editMode}
                      content={param.description}
                      onChange={handleChangeParam(param.name)}
                    />
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </ContentBox>
    )
  );
};

export default connect(
  state => ({
    editMode: state.documentation.editMode,
    params: state.documentation.method.params
  }),
  {
    updateMethodParam
  }
)(Params);
