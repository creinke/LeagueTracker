import React, {useState} from 'react';
import {View, Text, TextInput, Button, StyleSheet, Alert} from 'react-native';
import {useAuth} from '../../context/AuthContext';
import client from '../../api/client';
import {AuthResponse} from '../../types';

export default function LoginScreen() {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const {signIn} = useAuth();

    async function handleLogin() {
        try {
            const response = await client.post<AuthResponse>('/login', {
                username,
                password,
            });

            const {apiToken, user, league} = response.data;
            await signIn(apiToken, user, league);
        } catch (error) {
            Alert.alert('Login Error', 'Invalid credentials or server error.');
            console.error(error);
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
            <Button title="Login" onPress={handleLogin}/>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        justifyContent: 'center',
        padding: 20,
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
