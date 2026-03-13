import React from 'react';
import { View, Text, Button, StyleSheet } from 'react-native';
import { useAuth } from '../../context/AuthContext';

export default function HomeScreen({ navigation }: any) {
  const { user, league, signOut } = useAuth();

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Welcome, {user?.username}!</Text>
      <Text style={styles.subtitle}>Active League: {league?.name}</Text>
      
      <View style={styles.menu}>
        <Button title="Players" onPress={() => navigation.navigate('PlayerList')} />
        <View style={{ height: 10 }} />
        <Button title="Seasons" onPress={() => navigation.navigate('SeasonList')} />
      </View>

      <View style={styles.logout}>
        <Button title="Logout" color="red" onPress={signOut} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
    backgroundColor: '#fff',
  },
  title: {
    fontSize: 22,
    fontWeight: 'bold',
    marginBottom: 10,
  },
  subtitle: {
    fontSize: 18,
    marginBottom: 30,
    color: '#666',
  },
  menu: {
    width: '100%',
    marginBottom: 50,
  },
  logout: {
    width: '100%',
  },
});
