// import "./Result.less";

import React from "react";
import { connect } from "react-redux";
import { updateMethod } from "src/actions/documentation";
import { ContentBox } from "../../../../../../ui";
import { Input } from "src/ui";
import { getLang } from "src/utils/index";

const ShortDescription = ({ shortDescription, updateMethod, editMode }) => {
  const handleChange = value => updateMethod("short_description", value);

  if (!editMode) return null;

  return (
    <ContentBox className="Method__result">
      <h2>{getLang("cabinet_docsShortDescription")}</h2>
      <div className="Method__result__content">
        <Input multiLine value={shortDescription} onTextChange={handleChange} />
      </div>
    </ContentBox>
  );
};

export default connect(
  state => ({
    editMode: state.documentation.editMode,
    shortDescription: state.documentation.method.short_description
  }),
  {
    updateMethod
  }
)(ShortDescription);
