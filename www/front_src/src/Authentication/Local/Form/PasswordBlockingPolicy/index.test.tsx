import userEvent from '@testing-library/user-event';
import { Formik } from 'formik';

import { render, RenderResult, screen, waitFor } from '@centreon/ui';

import {
  PasswordSecurityPolicy,
  PasswordSecurityPolicyFromAPI,
} from '../../models';
import useValidationSchema from '../../useValidationSchema';
import {
  defaultPasswordSecurityPolicy,
  securityPolicyWithInvalidBlockingDuration,
} from '../defaults';
import {
  labelBlockingDurationMustBeLessThanOrEqualTo7Days,
  labelTimeThatMustPassBeforeNewConnection,
  labelChooseAValueBetween1and10,
  labelDay,
  labelDays,
  labelGood,
  labelMinutes,
  labelNumberOfAttemptsBeforeUserIsBlocked,
  labelPasswordBlockingPolicy,
  labelStrong,
  labelThisWillNotBeUsedBecauseNumberOfAttemptsIsNotDefined,
  labelWeak,
} from '../../translatedLabels';

import PasswordCasePolicy from '.';

const noOp = jest.fn();

interface Props {
  initialValues: PasswordSecurityPolicy;
}

const TestComponent = ({ initialValues }: Props): JSX.Element => {
  const validationSchema = useValidationSchema();

  return (
    <Formik<PasswordSecurityPolicy>
      enableReinitialize
      validateOnBlur
      validateOnMount
      initialValues={initialValues}
      validationSchema={validationSchema}
      onSubmit={noOp}
    >
      {(): JSX.Element => <PasswordCasePolicy />}
    </Formik>
  );
};

const renderPasswordBlockingPolicy = (
  initialValues: PasswordSecurityPolicyFromAPI = defaultPasswordSecurityPolicy,
): RenderResult =>
  render(
    <TestComponent initialValues={initialValues.password_security_policy} />,
  );

describe('Password Blocking Policy', () => {
  it('renders the password blocking policy fields with values', async () => {
    renderPasswordBlockingPolicy();

    await waitFor(() => {
      expect(screen.getByText(labelPasswordBlockingPolicy)).toBeInTheDocument();
    });

    expect(
      screen.getByLabelText(labelNumberOfAttemptsBeforeUserIsBlocked),
    ).toHaveValue(5);

    expect(
      screen.getByText(labelTimeThatMustPassBeforeNewConnection),
    ).toBeInTheDocument();

    expect(
      screen.getByLabelText(
        `${labelTimeThatMustPassBeforeNewConnection} ${labelMinutes}`,
      ),
    ).toHaveTextContent('15');

    expect(screen.getByText(labelWeak)).toBeInTheDocument();
  });

  it('displays an error message when the number of attempts is outside the bounds', async () => {
    renderPasswordBlockingPolicy();

    await waitFor(() => {
      expect(screen.getByText(labelPasswordBlockingPolicy)).toBeInTheDocument();
    });

    userEvent.type(
      screen.getByLabelText(labelNumberOfAttemptsBeforeUserIsBlocked),
      '0',
    );

    await waitFor(() => {
      expect(
        screen.getByText(labelChooseAValueBetween1and10),
      ).toBeInTheDocument();
    });

    userEvent.type(
      screen.getByLabelText(labelNumberOfAttemptsBeforeUserIsBlocked),
      '{selectall}{backspace}8',
    );

    await waitFor(() => {
      expect(
        screen.queryByText(labelChooseAValueBetween1and10),
      ).not.toBeInTheDocument();
    });

    userEvent.type(
      screen.getByLabelText(labelNumberOfAttemptsBeforeUserIsBlocked),
      '11',
    );

    await waitFor(() => {
      expect(
        screen.getByText(labelChooseAValueBetween1and10),
      ).toBeInTheDocument();
    });
  });

  it('displays an error message in the "Time blocking duration" field when the number of attempts is cleared', async () => {
    renderPasswordBlockingPolicy();

    await waitFor(() => {
      expect(screen.getByText(labelPasswordBlockingPolicy)).toBeInTheDocument();
    });

    userEvent.type(
      screen.getByLabelText(labelNumberOfAttemptsBeforeUserIsBlocked),
      '{selectall}{backspace}',
    );

    await waitFor(() => {
      expect(
        screen.getByText(
          labelThisWillNotBeUsedBecauseNumberOfAttemptsIsNotDefined,
        ),
      ).toBeInTheDocument();
    });
  });

  it('displays an error message when the time blocking duration is 7 days and 1 hour', async () => {
    renderPasswordBlockingPolicy({
      password_security_policy: securityPolicyWithInvalidBlockingDuration,
    });

    await waitFor(() => {
      expect(screen.getByText(labelPasswordBlockingPolicy)).toBeInTheDocument();
    });

    await waitFor(() => {
      expect(
        screen.getByText(labelBlockingDurationMustBeLessThanOrEqualTo7Days),
      ).toBeInTheDocument();
    });
  });

  it('displays the efficiency level when the number of attempts changes', async () => {
    renderPasswordBlockingPolicy();

    await waitFor(() => {
      expect(screen.getByText(labelPasswordBlockingPolicy)).toBeInTheDocument();
    });

    userEvent.type(
      screen.getByLabelText(labelNumberOfAttemptsBeforeUserIsBlocked),
      '{selectall}{backspace}2',
    );

    await waitFor(() => {
      expect(screen.getByText(labelStrong)).toBeInTheDocument();
    });

    userEvent.type(
      screen.getByLabelText(labelNumberOfAttemptsBeforeUserIsBlocked),
      '{selectall}{backspace}4',
    );

    await waitFor(() => {
      expect(screen.getByText(labelGood)).toBeInTheDocument();
    });
  });

  it('displays the efficiency level when the time blocking duration changes', async () => {
    renderPasswordBlockingPolicy();

    await waitFor(() => {
      expect(screen.getByText(labelPasswordBlockingPolicy)).toBeInTheDocument();
    });

    userEvent.click(
      screen.getByLabelText(
        `${labelTimeThatMustPassBeforeNewConnection} ${labelDay}`,
      ),
    );
    userEvent.click(screen.getByText('6'));

    await waitFor(() => {
      expect(screen.getByText(labelStrong)).toBeInTheDocument();
    });

    userEvent.click(
      screen.getByLabelText(
        `${labelTimeThatMustPassBeforeNewConnection} ${labelDays}`,
      ),
    );
    userEvent.click(screen.getByText('3'));

    userEvent.click(
      screen.getByLabelText(
        `${labelTimeThatMustPassBeforeNewConnection} ${labelMinutes}`,
      ),
    );
    userEvent.click(screen.getAllByText('0')[1]);

    await waitFor(() => {
      expect(screen.getByText(labelGood)).toBeInTheDocument();
    });
  });
});
