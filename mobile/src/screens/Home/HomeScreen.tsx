import React, {useEffect, useState} from 'react';
import {View, Text, Button, StyleSheet, Alert} from 'react-native';
import {useAuth} from '../../context/AuthContext';
import EventService from '../../services/EventService';

export default function HomeScreen({navigation}: any) {
    const {user, league, signOut} = useAuth();
    const [shortcuts, setShortcuts] = useState<{
        nextEvent: { id: number; eventNumber: number; description: string } | null;
        lastEvent: { id: number; eventNumber: number; description: string } | null;
    } | null>(null);

    useEffect(() => {
        const unsubscribe = navigation.addListener('focus', () => {
            loadShortcuts();
        });
        return unsubscribe;
    }, [navigation]);

    const loadShortcuts = async () => {
        try {
            const data = await EventService.getShortcuts();
            setShortcuts(data);
        } catch (error) {
            console.error('Failed to load event shortcuts', error);
        }
    };

    const handleNextEventPairings = () => {
        if (shortcuts?.nextEvent) {
            navigation.navigate('GameList', {
                eventId: shortcuts.nextEvent.id,
                eventNumber: shortcuts.nextEvent.eventNumber
            });
        } else {
            Alert.alert('No Event', 'No upcoming event found.');
        }
    };

    const handlePostLastEventScores = () => {
        if (shortcuts?.lastEvent) {
            navigation.navigate('GameList', {
                eventId: shortcuts.lastEvent.id,
                eventNumber: shortcuts.lastEvent.eventNumber
            });
        } else {
            Alert.alert('No Event', 'No previous event found.');
        }
    };

    const handleLastEventResults = () => {
        if (shortcuts?.lastEvent) {
            navigation.navigate('EventResults', {
                eventId: shortcuts.lastEvent.id,
                eventNumber: shortcuts.lastEvent.eventNumber
            });
        } else {
            Alert.alert('No Event', 'No previous event found.');
        }
    };

    return (
        <View style={styles.container}>
            <Text style={styles.title}>Welcome, {user?.username}!</Text>
            <Text style={styles.subtitle}>{league?.name}</Text>

            <View style={styles.menu}>
                <Button title="Players" onPress={() => navigation.navigate('PlayerList')}/>
                <View style={{height: 10}}/>
                <Button title="Seasons" onPress={() => navigation.navigate('SeasonList')}/>
                
                <View style={{height: 10}}/>
                {shortcuts?.nextEvent && (
                    <>
                        <Button title="Display Next Event Pairings" onPress={handleNextEventPairings}/>
                        <View style={{height: 10}}/>
                    </>
                )}
                {shortcuts?.lastEvent && (
                    <>
                        <Button title="Post Last Event Scores" onPress={handlePostLastEventScores}/>
                        <View style={{height: 10}}/>
                        <Button title="Display Last Event Results" onPress={handleLastEventResults}/>
                        <View style={{height: 10}}/>
                    </>
                )}
            </View>

            <View style={styles.footer}>
                <Button title="Logout" color="red" onPress={signOut}/>
                <View style={{height: 10}}/>
                <Button title="Help" onPress={() => navigation.navigate('Help')}/>
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
        width: '100%',
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
        marginBottom: 20,
    },
    footer: {
        width: '100%',
    },
});
