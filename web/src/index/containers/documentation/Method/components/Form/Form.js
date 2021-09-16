import "./Form.less";

import React, { useState, useEffect } from "react";
import { connect } from "react-redux";
import { updateMethod } from "src/actions/documentation";
import { Button, Code, ContentBox } from "src/ui";
import { invoke } from "src/services/api";
import Field from "./Field";
import * as toast from "src/actions/toasts";
import * as utils from "../../../../../../utils";
import { isJson } from "../../../../../../utils";
import * as auth from "../../../../../../services/auth";

const Form = ({
  method,
  resultExample,
  params,
  path,
  updateMethod,
  editMode
}) => {
  const [formData, setFormData] = useState({});
  const [response, setResponse] = useState(null);
  const [requestStatus, setRequestStatus] = useState("");

  const handleSetProperty = key => value => {
    setFormData({ ...formData, [key]: value });
  };

  useEffect(() => {
    const defaultFormData = {};
    params.forEach(param => {
      let value = null;
      if (param.filters.default) value = param.filters.default;
      if (param.filters.oneOf) value = param.filters.oneOf[0];
      if (param.filters.double) value = 1.0;
      if (param.filters.positive || param.filters.int) value = 0;
      if (param.filters.min || param.filters.int)
        value = param.filters.min || param.filters.int;
      if (param.name === "ga_code") value = "";
      if (param.name.toUpperCase() === "X_TOKEN") value = auth.getToken();
      if (param.name === "app_id") value = "8";

      if (value !== null) defaultFormData[param.name] = value;
    });
    setFormData(defaultFormData);
  }, [params]);

  const handleSubmit = e => {
    e.preventDefault();
    setRequestStatus("loading");
    invoke(method, path, formData, { redirect: false })
      .then(response => {
        setResponse(response);
      })
      .catch(err => {
        toast.error(err.message);
      })
      .finally(() => {
        setRequestStatus("");
      });
  };

  const handleChange = e => {
    const value = e.target.value;
    updateMethod("result_example", value);
  };

  return (
    <ContentBox className="MethodForm">
      <form onSubmit={handleSubmit}>
        <div className="MethodForm__grid headers">
          {params
            .filter(p => p.type === "header")
            .map(param => (
              <label className="MethodForm__field">
                <div className="MethodForm__field__label">{param.name}</div>
                <Field
                  key={param.name}
                  param={param}
                  value={formData[param.name]}
                  onChange={handleSetProperty(param.name)}
                />
              </label>
            ))}
        </div>
        <div className="MethodForm__grid">
          {params
            .filter(p => p.type === "body")
            .map(param => (
              <label className="MethodForm__field">
                <div className="MethodForm__field__label">{param.name}</div>
                <Field
                  key={param.name}
                  param={param}
                  value={formData[param.name]}
                  onChange={handleSetProperty(param.name)}
                />
              </label>
            ))}
        </div>
        <Button state={requestStatus} btnType="submit">
          {utils.getLang("global_submit")}
        </Button>
      </form>
      {response ? (
        <Code type="json" className="MethodForm__response">
          {JSON.stringify(response, null, 2)}
        </Code>
      ) : editMode ? (
        <Code type="json" simple className="MethodForm__response">
          <textarea
            className="MethodForm__response__editor"
            onChange={handleChange}
          >
            {isJson(resultExample)
              ? JSON.stringify(JSON.parse(resultExample), null, 2)
              : resultExample}
          </textarea>
        </Code>
      ) : (
        <Code type="json" className="MethodForm__response">
          {isJson(resultExample)
            ? JSON.stringify(JSON.parse(resultExample), null, 2)
            : resultExample}
        </Code>
      )}
    </ContentBox>
  );
};

export default connect(
  state => ({
    editMode: state.documentation.editMode,
    resultExample: state.documentation.method.result_example,
    path: state.documentation.method.path,
    method: state.documentation.method.method,
    params: state.documentation.method.params
  }),
  {
    updateMethod
  }
)(Form);
