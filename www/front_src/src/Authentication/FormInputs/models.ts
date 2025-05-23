import { FormikValues } from 'formik';

import { SvgIconProps } from '@mui/material';

export enum InputType {
  Switch,
  Radio,
  Text,
  Multiple,
  Password,
  ConnectedAutocomplete,
  FieldsTable,
}

interface FieldsTableGetRequiredProps {
  index: number;
  values: FormikValues;
}

export interface InputProps {
  additionalFieldsToMemoize?: Array<string>;
  additionalLabel?: string;
  additionalMemoProps?: Array<unknown>;
  category: string;
  change?: ({ setFieldValue, value }) => void;
  endpoint?: string;
  fieldName: string;
  fieldsTableConfiguration?: {
    columns: Array<Omit<InputProps, 'category'>>;
    defaultRowValue: object;
    deleteLabel: string;
    getRequired?: ({ values, index }: FieldsTableGetRequiredProps) => boolean;
  };
  filterKey?: string;
  getChecked?: (value) => boolean;
  getDisabled?: (values: FormikValues) => boolean;
  getRequired?: (values: FormikValues) => boolean;
  label: string;
  options?: Array<{
    isChecked: (value) => boolean;
    label: string;
    value: boolean;
  }>;
  required?: boolean;
  type: InputType;
}

export type InputPropsWithoutCategory = Omit<InputProps, 'category'>;

export interface Category {
  EndIcon?: (props: SvgIconProps) => JSX.Element;
  TooltipContent?: () => JSX.Element;
  name: string;
  order: number;
}
