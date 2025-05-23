import { useMemo, useEffect } from 'react';

import { useTranslation } from 'react-i18next';
import { isNil, not } from 'ramda';

import { LinearProgress, Typography } from '@mui/material';
import { makeStyles } from '@mui/styles';

import LoadingSkeletonForm from '../FormInputs/LoadingSkeleton';
import useTab from '../useTab';

import { labelDefineWebSSOConfiguration } from './translatedLabels';
import useWebSSO from './useWebSSO';
import Form from './Form';
import { WebSSOConfiguration } from './models';
import { inputs } from './Form/inputs';

const useStyles = makeStyles((theme) => ({
  container: {
    width: 'fit-content',
  },
  loading: {
    height: theme.spacing(0.5),
  },
  paper: {
    padding: theme.spacing(2),
  },
}));

const WebSSOConfigurationForm = (): JSX.Element => {
  const classes = useStyles();
  const { t } = useTranslation();

  const {
    sendingGetWebSSOConfiguration,
    initialWebSSOConfiguration,
    loadWebSSOonfiguration,
  } = useWebSSO();

  const isWebSSOConfigurationEmpty = useMemo(
    () => isNil(initialWebSSOConfiguration),
    [initialWebSSOConfiguration],
  );

  useTab(isWebSSOConfigurationEmpty);

  useEffect(() => {
    loadWebSSOonfiguration();
  }, []);

  return (
    <div>
      <Typography variant="h4">{t(labelDefineWebSSOConfiguration)}</Typography>
      <div className={classes.loading}>
        {not(isWebSSOConfigurationEmpty) && sendingGetWebSSOConfiguration && (
          <LinearProgress />
        )}
      </div>
      {isWebSSOConfigurationEmpty ? (
        <LoadingSkeletonForm inputs={inputs} />
      ) : (
        <Form
          initialValues={initialWebSSOConfiguration as WebSSOConfiguration}
          loadWebSSOonfiguration={loadWebSSOonfiguration}
        />
      )}
    </div>
  );
};

export default WebSSOConfigurationForm;
