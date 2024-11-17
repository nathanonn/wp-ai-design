import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextField, InputAdornment, IconButton } from '@mui/material';
import { Visibility, VisibilityOff } from '@mui/icons-material';

function ApiKeyField({ provider, apiKey, onChange, label }) {
  const [showApiKey, setShowApiKey] = useState(false);

  return (
    <TextField
      fullWidth
      label={label || __('API Key', 'ai-design-block')}
      type={showApiKey ? 'text' : 'password'}
      value={apiKey}
      onChange={(e) => onChange(e.target.value)}
      margin="normal"
      InputProps={{
        endAdornment: (
          <InputAdornment position="end">
            <IconButton
              aria-label="toggle password visibility"
              onClick={() => setShowApiKey(!showApiKey)}
              edge="end"
            >
              {showApiKey ? <VisibilityOff /> : <Visibility />}
            </IconButton>
          </InputAdornment>
        ),
      }}
      sx={{
        '& .MuiInputLabel-root': {
          transform: 'translate(14px, 16px) scale(1)',
        },
        '& .MuiInputLabel-shrink': {
          transform: 'translate(14px, -6px) scale(0.75)',
        },
        '& .MuiInputBase-root': {
          fontSize: '1.1rem',
          padding: '8px 14px',
        },
        '& input[type="password"],& input[type="text"]': {
          border: 'none',
          '&:focus': {
            border: 'none',
            outline: 'none',
            boxShadow: 'none',
          },
        },
      }}
    />
  );
}

export default ApiKeyField;