import React, {useState} from 'react';
import {View, Text, TextInput, Button, StyleSheet, Alert} from 'react-native';
import {useAuth} from '../../context/AuthContext';
import client from '../../api/client';
import {AuthResponse} from '../../types';

export default function LoginScreen() {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const {signIn} = useAuth();

    async function handleLogin() {
        if (!username || !password) {
            Alert.alert('Login', 'Please enter both username and password.');
            return;
        }

        setLoading(true);
        try {
            console.log(`Attempting login for ${username} to ${client.defaults.baseURL}`);
            const response = await client.post<AuthResponse>('/login', {
                username,
                password,
            });

            console.log('Login successful, signing in...');
            const {apiToken, user, league} = response.data;
            await signIn(apiToken, user, league);
        } catch (error: any) {
            console.error('Login error details:', error);
            let message = 'Invalid credentials or server error.';
            if (error.response) {
                message = `Server returned ${error.response.status}: ${JSON.stringify(error.response.data)}`;
            } else if (error.request) {
                message = 'No response from server. Check your network connection and API_BASE_URL.';
            } else {
                message = error.message;
            }
            Alert.alert('Login Error', message);
        } finally {
            setLoading(false);
        }
    }

    return (
        <View style={styles.container}>
            <Text style={styles.title}>League Tracker Login</Text>
            <TextInput
                style={styles.input}
                placeholder="Username"
                value={username}
                onChangeText={setUsername}
                autoCapitalize="none"
            />
            <TextInput
                style={styles.input}
                placeholder="Password"
                secureTextEntry
                value={password}
                onChangeText={setPassword}
            />
            <Button title={loading ? "Logging in..." : "Login"} onPress={handleLogin} disabled={loading}/>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        justifyContent: 'center',
        padding: 20,
        width: '100%',
    },
    title: {
        fontSize: 24,
        marginBottom: 20,
        textAlign: 'center',
    },
    input: {
        borderWidth: 1,
        borderColor: '#ccc',
        padding: 10,
        marginBottom: 10,
        borderRadius: 5,
    },
});
