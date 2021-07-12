const yaml = require("js-yaml");
const fs = require("fs");
const Ajv = require("ajv");
const ajv = new Ajv();

const schema = fs.readFileSync("./schema.json", "utf8");

const validate = ajv.compile(JSON.parse(schema));

const files = [
  "./Configuration/Settings.yaml",
  "./Configuration/Settings.Translations.yaml",
];

files.forEach((file) => {
  const data = yaml.load(fs.readFileSync(file, "utf8"));
  const valid = validate(data);
  if (!valid) {
    console.log(validate.errors);
  } else {
    console.log(file + " -> VALID");
  }
});
