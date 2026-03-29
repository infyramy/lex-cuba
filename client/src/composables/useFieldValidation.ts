import { reactive } from "vue";

type FieldRule = {
  required?: boolean;
  message?: string;
};

type Rules = Record<string, FieldRule>;

export function useFieldValidation(rules: Rules) {
  const errors = reactive<Record<string, string>>({});

  function validate(field: string, value: unknown): boolean {
    const rule = rules[field];
    if (!rule) return true;

    if (rule.required) {
      const empty = value === undefined || value === null || value === "" || (Array.isArray(value) && value.length === 0);
      if (empty) {
        errors[field] = rule.message || `${field} is required`;
        return false;
      }
    }

    delete errors[field];
    return true;
  }

  function validateAll(formValues: Record<string, unknown>): boolean {
    let valid = true;
    for (const field of Object.keys(rules)) {
      if (!validate(field, formValues[field])) {
        valid = false;
      }
    }
    return valid;
  }

  function clearError(field: string) {
    delete errors[field];
  }

  function hasError(field: string): boolean {
    return !!errors[field];
  }

  return { errors, validate, validateAll, clearError, hasError };
}
