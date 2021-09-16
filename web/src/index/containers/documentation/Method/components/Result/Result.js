import "./Result.less";

import React from "react";
import { connect } from "react-redux";
import { updateMethod } from "src/actions/documentation";
import { ContentBox } from "../../../../../../ui";
import { Editor } from "src/ui";
import { getLang } from "../../../../../../utils";

const Result = ({ result, updateMethod, editMode }) => {
  const handleChange = value => updateMethod("result", value);

  return (
    <ContentBox className="Method__result">
      <h2>{getLang("cabinet_docsResult")}</h2>
      <div className="Method__result__content">
        <Editor
          readOnly={!editMode}
          border={editMode}
          content={result}
          onChange={handleChange}
        />
      </div>
    </ContentBox>
  );
};

export default connect(
  state => ({
    editMode: state.documentation.editMode,
    result: state.documentation.method.result
  }),
  {
    updateMethod
  }
)(Result);
